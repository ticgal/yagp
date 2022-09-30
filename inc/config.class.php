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

class PluginYagpConfig extends CommonDBTM {
   static private $_instance = null;

   public function __construct() {
      global $DB;
      if ($DB->tableExists(self::getTable())) {
         $this->getFromDB(1);
      }
   }
   /**
   * Summary of canCreate
   * @return boolean
   */
   static function canCreate() {
      return Session::haveRight('config', UPDATE);
   }

   /**
   * Summary of canView
   * @return boolean
   */
   static function canView() {
      return Session::haveRight('config', READ);
   }

   /**
   * Summary of canUpdate
   * @return boolean
   */
   static function canUpdate() {
      return Session::haveRight('config', UPDATE);
   }

   /**
   * Summary of getTypeName
   * @param mixed $nb plural
   * @return mixed
   */
   static function getTypeName($nb = 0) {
      return __("Yagp", "yagp");
   }

   /**
   * Summary of getInstance
   * @return PluginProcessmakerConfig
   */
   static function getInstance() {

      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
      }
      return self::$_instance;
   }

   public static function getConfig($update = false) {
      static $config = null;
      if (is_null($config)) {
         $config = new self();
      }
      if ($update) {
         $config->getFromDB(1);
      }
      return $config;
   }

   /**
   * Summary of showConfigForm
   * @param mixed $item is the config
   * @return boolean
   */
   static function showConfigForm() {
      global $CFG_GLPI;

      $config = new self();
      $config->getFromDB(1);

      $config->showFormHeader(['colspan' => 4]);

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__("Change ticket solved date to last task end time", "yagp")."</td><td >";
      Dropdown::showYesNo("ticketsolveddate", $config->fields["ticketsolveddate"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__("Auto renew tacit contracts", "yagp")."</td><td >";
      Dropdown::showYesNo("contractrenew", $config->fields["contractrenew"]);
      echo "</td></tr>\n";

      /**** Deprecated
       * echo "<tr class='tab_bg_1'>";
       * echo "<td >".__("Fixed Menu", "yagp")."</td><td >";
       * Dropdown::showYesNo("fixedmenu", $config->fields["fixedmenu"]);
       * echo "</td></tr>\n";
      ****/
      
      echo "<tr class='tab_bg_1'>";
      echo "<td >".__("Go to ticket", "yagp")."</td><td >";
      Dropdown::showYesNo("gototicket", $config->fields["gototicket"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__("Block opening date", "yagp")."</td><td >";
      Dropdown::showYesNo("blockdate", $config->fields["blockdate"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__("Replace ticket requester (mailcollector)", "yagp")."</td><td >";
      Dropdown::showYesNo("findrequest", $config->fields["findrequest"]);
      echo "</td></tr>\n";

      if ($config->fields['findrequest']) {
         echo "<tr class='tab_bg_1'>";
         echo "<td >".__("Tag to search", "yagp")."</td><td >";
         echo Html::input("requestlabel", ['value' => $config->fields["requestlabel"]]);
         echo "</td></tr>\n";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__("Change default minimum validation required", "yagp")."</td><td >";
      Dropdown::showYesNo("change_df_min_val", $config->fields["change_df_min_val"]);
      echo "</td></tr>\n";

      if ($config->fields['change_df_min_val']) {
         echo "<tr class='tab_bg_1'>";
         echo "<td >".__("Default minimum validation required", "yagp")."</td><td >";
         $possible_values = [];
         $possible_values[0]="0%";
         $possible_values[50]="50%";
         $possible_values[100]="100%";
         Dropdown::showFromArray('df_min_validation', $possible_values,['value' => $config->fields["df_min_validation"]]);
         echo "</td></tr>\n";
      }

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__("Enable re-categorization tracking", "yagp")."</td><td >";
      Dropdown::showYesNo("recategorization", $config->fields["recategorization"]);
      echo "</td></tr>\n";

      echo "<tr class='tab_bg_1'>";
      echo "<td >".__("Hide historical tab to post-only users", "yagp")."</td><td >";
      Dropdown::showYesNo("hide_historical", $config->fields["hide_historical"]);
      echo "</td></tr>\n";

      $config->showFormButtons(['candel'=>false]);

      return false;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      global $LANG;

      if ($item->getType()=='Config') {
         return __("Yagp", "yagp");
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType()=='Config') {
         self::showConfigForm($item);
      }
      return true;
   }

   public static function install(Migration $migration) {
      global $DB;

      $table  = self::getTable();
      $config = new self();

      if (!$DB->tableExists($table) && !$DB->tableExists("glpi_plugin_yagp_config")) {
         $migration->displayMessage("Installing $table");
         //Install

         $query = "CREATE TABLE `$table` (
							`id` int(11) NOT NULL auto_increment,
                     `ticketsolveddate` tinyint(1) NOT NULL default '0',
                     `contractrenew` tinyint(1) NOT NULL default '0',
                     `gototicket` tinyint(1) NOT NULL default '0',
                     `blockdate` tinyint(1) NOT NULL default '0',
                     `findrequest` tinyint(1) NOT NULL default '0',
                     `requestlabel` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                     `change_df_min_val` tinyint(1) NOT NULL default '0',
                     `df_min_validation` int(11) NOT NULL default '0',
                     `recategorization` tinyint(1) NOT NULL default '0',
                     `hide_historical` tinyint(1) NOT NULL default '0',
                     PRIMARY KEY  (`id`)
                  ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
         $DB->query($query) or die ($DB->error());
         $config->add([
                  'id'                          => 1,
                  'ticketsolveddate'            => 0,
                  'contractrenew'               => 0,
                  'gototicket'                  => 0,
                  'blockdate'                   => 0,
                  'findrequest'                 => 0,
                  'change_df_min_val'           => 0,
                  'df_min_validation'           => 0,
               ]);
      }else{
      	$migration->addField($table, 'gototicket', 'boolean');
         $migration->addField($table, 'blockdate', 'boolean');
         $migration->addField($table, 'findrequest', 'boolean');
         $migration->addField($table, 'requestlabel', 'string');
         $migration->addField($table, 'change_df_min_val', 'boolean');
         $migration->addField($table, 'df_min_validation', 'int');
      	$migration->migrationOneTable($table);
      }
   }
}
