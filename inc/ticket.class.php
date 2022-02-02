<?php
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
				}
			}
		}

		return $ticket;
	}
}