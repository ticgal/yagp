<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginYagpAutosave extends CommonDBTM {

	static function getTypeName($nb = 0) {
		return __("Yagp", "yagp");
	}

	static public function postForm($params){
		$item = $params['item'];
		
		switch ($item->getType()) {
			case Ticket::getType():
				if (!$item->getID()) {
					$itemtype=$item->getType()."_".$_SESSION['glpiactive_entity'];

					self::addAutosave($itemtype);
				}
			break;
			case 'ITILFollowup':
				if (!$item->getID()) {
					$itemtype=$item->getType()."_".$_SESSION['glpiactive_entity']."_".$item->fields['items_id'];

					self::addAutosave($itemtype);
				}
			break;
			case 'TicketTask':
				if (!$item->getID()) {
					$itemtype=$item->getType()."_".$_SESSION['glpiactive_entity']."_".$item->fields['tickets_id'];

					self::addAutosave($itemtype);
				}
			break;
			case 'ITILSolution':
				if (!$item->getID()) {
					$itemtype=$item->getType()."_".$_SESSION['glpiactive_entity']."_".$item->fields['items_id'];

					self::addAutosave($itemtype);
				}
			break;
		}
	}

	public static function addAutosave($itemtype){
		$script=<<<JAVASCRIPT
			$(document).ready(function(){
				if(typeof(Storage) !== "undefined") {
					if (localStorage.getItem("{$itemtype}") !== null) {
						var content=localStorage.getItem("{$itemtype}");
						$('<div>'+content+'</div>').appendTo('body').dialog({
							modal: true,
							title: 'Load draft?',
							autoOpen: true,
							width: '1050px',
							resizable: false,
							buttons: {
								Yes: function() {
									var iframe=$("div.mce-tinymce iframe").contents();
									$(iframe[0].body).html(content);
									$(this).dialog("close");
								},
								No: function() {
									$(this).dialog("close");
								}
							},
							close: function(event, ui) {
								localStorage.removeItem("{$itemtype}");
								$(this).remove();
							},
							open: function(event, ui){
								$("div.ui-dialog-buttonset").css({"width":"50%","margin": "0 auto","float":"initial"});
								$(".ui-dialog-buttonpane button:first").css({"float":"left"});
								$(".ui-dialog-buttonpane button:last").css({"float":"right"});
							}
						});

						setInterval(function () {
							var iframe=$("div.mce-tinymce iframe").contents();
							var html=$(iframe[0].body).html();
							if ($(html).find("br[data-mce-bogus]").length===0) {
								localStorage.setItem("{$itemtype}", $(iframe[0].body).html());
							}
						},5000);
					}else{
						setInterval(function () {
							var iframe=$("div.mce-tinymce iframe").contents();
							var html=$(iframe[0].body).html();
							if ($(html).find("br[data-mce-bogus]").length===0) {
								localStorage.setItem("{$itemtype}",$(iframe[0].body).html());
							}
						},5000);
					}
				}
			});
JAVASCRIPT;
		echo Html::scriptBlock($script);
	}
}