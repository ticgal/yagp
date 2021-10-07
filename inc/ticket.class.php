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
}