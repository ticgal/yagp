<?php
/*
 -------------------------------------------------------------------------
 YAGP plugin for GLPI
 Copyright (C) 2019-2022 by the TICgal Team.
 https://tic.gal/en/project/yagp-yet-another-glpi-plugin/
 -------------------------------------------------------------------------
 LICENSE
 This file is part of the YAGP plugin.
 YAGP plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.
 YAGP plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with YAGP. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   YAGP
 @author    the TICgal team
 @copyright Copyright (c) 2019-2022 TICgal team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://tic.gal/en/project/yagp-yet-another-glpi-plugin/
 @since     2019-2022
 ----------------------------------------------------------------------
*/
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginYagpTicket extends CommonDBTM {
	
	static function postItemForm($params = []) {
		global $DB;

		$item = $params['item'];
		if (!is_array($item) && $item->getType() == Ticket::getType()) {
			$date = ($item->getID()) ? $item->fields['date'] : '' ;
			$script = <<<JAVASCRIPT
			$(document).ready(function() {
				console.log($("input[name='date']").parent());
				$("input[name='date']").parent().parent().html("{$date}");
			});
JAVASCRIPT;
			echo Html::scriptBlock($script);
		}
	}

	public static function preAddTicket(Ticket $ticket) {
		global $DB;

      $config = PluginYagpConfig::getConfig();
		$pattern = "/".$config->fields['requestlabel'].".*".$config->fields['requestlabel']."/i";

		if (isset($ticket->input['_message'])) {
			$mail = $ticket->input['_message'];
			if (preg_match_all($pattern, $mail->getContent(), $matches)) {
				$string = $matches[0];
				$useremail = str_replace($config->fields['requestlabel'], "", $string);
				$user = new User();
				if ($user->getFromDBbyEmail($useremail)) {
					$ticket->input['_users_id_requester'] = $user->fields['id'];

					$mailgate = new MailCollector();
					$mailgate->getFromDB($ticket->input['_mailgate']);
					$rule_options['ticket']              = $ticket->input;
         		$rule_options['headers']             = $mailgate->getHeaders($ticket->input['_message']);
         		$rule_options['mailcollector']       = $ticket->input['_mailgate'];
         		$rule_options['_users_id_requester'] = $ticket->input['_users_id_requester'];
         		$rulecollection                      = new RuleMailCollectorCollection();
         		$output                              = $rulecollection->processAllRules([], [],
                                                                                 $rule_options);
         		foreach ($output as $key => $value) {
	               $ticket->input[$key] = $value;
	            }
				}
			}
		}

		return $ticket;
	}
}