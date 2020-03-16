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
         case 'Ticket':
         if (!$item->getID()) {
         	$itemtype=$item->getType();

         	$script=<<<JAVASCRIPT
				$(document).ready(function(){
					jQuery.fn.exists = function(){ return this.length > 0; }
         			if(typeof(Storage) !== "undefined") {
         				if (localStorage.getItem("{$itemtype}") !== null) {
         					$('<div></div>').appendTo('body').dialog({
         						modal: true,
         						title: 'Load draft?',
         						autoOpen: true,
         						width: 'auto',
         						resizable: false,
         						buttons: {
         							Yes: function() {
         								var content=localStorage.getItem("{$itemtype}");
         								$("textarea[name='content']").val(content);
         								var iframe=$("div.mce-tinymce iframe").contents()
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
         						}
         					});
         					
         					setInterval(function () {
         						localStorage.setItem("{$itemtype}", $("textarea[name='content']").val());
         					},5000);
         				}else{
         					setInterval(function () {
         						localStorage.setItem("{$itemtype}", $("textarea[name='content']").val());
         					},5000);
         				}
         			}
         		});
JAVASCRIPT;
			echo Html::scriptBlock($script);
         }
         break;
      }
   }
}