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
			if ($date!=null){
				echo Html::scriptBlock($script);
			}
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
				if ($user->getFromDBbyEmail($useremail[0])) {
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

	public static function updateTicket(Ticket $ticket) {

		if(isset($ticket->oldvalues["itilcategories_id"])){
			if ($ticket->oldvalues["itilcategories_id"]===0){
				$ticket_cat=new self();
				$ticket_cat->getFromDBByCrit(["tickets_id"=>$ticket->fields["id"]]);
				if(empty($ticket_cat->fields)){
					$ticket_cat->add(["tickets_id"=>$ticket->fields["id"],"itilcategories_id"=>$ticket->fields["itilcategories_id"]]);
				}
			}
		}
		
	}

	public static function addTicket(Ticket $ticket) {

		if(isset($ticket->fields["itilcategories_id"])){
			if ($ticket->fields["itilcategories_id"]!==0){
				$ticket_cat=new self();
				$ticket_cat->getFromDBByCrit(["tickets_id"=>$ticket->fields["id"]]);
				if(empty($ticket_cat->fields)){
					$ticket_cat->add(["tickets_id"=>$ticket->fields["id"],"itilcategories_id"=>$ticket->fields["itilcategories_id"]]);
				}
			}
		}
		
	}

	public static function plugin_yagp_postItemForm($params) {

		if (isset($params['item']) && $params['item'] instanceof CommonDBTM) {
			switch (get_class($params['item'])) {
			   case 'Ticket':
				if ($params['item']->getID()) {
					$id=$params['item']->getID();
					$ticket_cat=new self();
					$ticket_cat->getFromDBByCrit(["tickets_id"=>$id]);
					if(!empty($ticket_cat->fields)){
						$cat = new ITILCategory();
						$cat->getFromDB($ticket_cat->fields["itilcategories_id"]);
						if(!empty($cat->fields)){
							$cat_name=$cat->fields["name"];
						$script=<<<JAVASCRIPT
						$(document).ready(function(){
							if( $('#recategorized').length ==0)  {
								$("span[id^='category_block_']").after("<div id='recategorized' class='form-field row col-12 mb-2'><label class='col-form-label col-xxl-4 text-xxl-end'>" + __("Initial category","yagp") + "</label>"+'<div class="col-xxl-8  field-container"><span class="entity-badge" title="techs-tickets"><span class="text-nowrap">'+"{$cat_name}"+'</span></span></div>'+"</div>");
							}
							
						});
JAVASCRIPT;
						echo Html::scriptBlock($script);
						}
						
					}

				}

			}
		}
	  
		

	}

	static function install(Migration $migration)
	{
		global $DB;

		$default_charset = DBConnection::getDefaultCharset();
		$default_collation = DBConnection::getDefaultCollation();
		$default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

		$table = self::getTable();
		if (!$DB->tableExists($table)) {
			$migration->displayMessage("Installing $table");
			$query = "CREATE TABLE IF NOT EXISTS $table (
				`id` int {$default_key_sign} NOT NULL auto_increment,
				`tickets_id` INT {$default_key_sign} NOT NULL,
				`itilcategories_id` INT {$default_key_sign} NOT NULL,
				PRIMARY KEY (`id`),
				UNIQUE KEY `unicity` (`tickets_id`),
				KEY `tickets_id` (`tickets_id`)
				) ENGINE=InnoDB DEFAULT CHARSET={$default_charset} COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
			$DB->query($query) or die($DB->error());
		}
	}

}