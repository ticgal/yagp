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
/**
 * Install all necessary elements for the plugin
 *
 * @return boolean True if success
 */
function plugin_yagp_install() {

   $migration = new Migration(PLUGIN_YAGP_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginYagp' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'install')) {
            $classname::install($migration);
         }
      }
   }

   return true;
}
/**
 * Uninstall previously installed elements of the plugin
 *
 * @return boolean True if success
 */
function plugin_yagp_uninstall() {

   $migration = new Migration(PLUGIN_YAGP_VERSION);

   // Parse inc directory
   foreach (glob(dirname(__FILE__).'/inc/*') as $filepath) {
      // Load *.class.php files and get the class name
      if (preg_match("/inc.(.+)\.class.php/", $filepath, $matches)) {
         $classname = 'PluginYagp' . ucfirst($matches[1]);
         include_once($filepath);
         // If the install method exists, load it
         if (method_exists($classname, 'uninstall')) {
            $classname::uninstall($migration);
         }
      }
   }

   return true;
}

function plugin_yagp_updateitem(CommonDBTM $item) {
   if ($item::getType()=="PluginYagpConfig") {
      $input=$item->input;
      if ($input["ticketsolveddate"]==1) {
         Crontask::Register("PluginYagpTicketsolveddate", 'changeDate', HOUR_TIMESTAMP, [
            'state'=>1,
            'mode'  => CronTask::MODE_EXTERNAL
         ]);
      } else if ($input["ticketsolveddate"]==0) {
         Crontask::Unregister("YagpTicketsolveddate");
      }
      if ($input["contractrenew"]==1) {
         Crontask::Register("PluginYagpContractrenew", 'renewContract', DAY_TIMESTAMP, [
            'state'=>1,
            'mode'  => CronTask::MODE_EXTERNAL
         ]);
      } else if ($input["contractrenew"]==0) {
         Crontask::Unregister("YagpContractrenew");
      }
   }
}

function plugin_yagp_getAddSearchOptions($itemtype){

   $sopt=[];

	switch ($itemtype){
      case "Ticket":
         $sopt['yagp']=['name'=>'YAGP'];

         $sopt[9021321] = [
				'table' => PluginYagpTicket::getTable(),
				'field' => 'plugin_yagp_itilcategories_id',
				'name' => __('Recategorized','yagp'),
				'searchtype' => ['equals', 'notequals'],
				'massiveaction' => false,
				'datatype' => 'specific',
				'joinparams' => [
					'jointype' => 'child',
               'linkfield'=>'tickets_id'
				]
			];
   }

   return $sopt;
}
