<?php

/*
 -------------------------------------------------------------------------
 YAGP plugin for GLPI
 Copyright (C) 2019-2023 by the TICgal Team.
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
 @copyright Copyright (c) 2019-2023 TICgal team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://tic.gal/en/project/yagp-yet-another-glpi-plugin/
 @since     2019-2023
 ----------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginYagpConfig extends CommonDBTM
{
    private static $_instance = null;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        global $DB;
        if ($DB->tableExists(self::getTable())) {
            $this->getFromDB(1);
        }
    }

    /**
    * Summary of canCreate
    * @return boolean
    */
    public static function canCreate(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    /**
    * Summary of canView
    * @return boolean
    */
    public static function canView(): bool
    {
        return Session::haveRight('config', READ);
    }

    /**
    * Summary of canUpdate
    * @return boolean
    */
    public static function canUpdate(): bool
    {
        return Session::haveRight('config', UPDATE);
    }

    /**
    * Summary of getTypeName
    * @param mixed $nb plural
    * @return mixed
    */
    public static function getTypeName($nb = 0): string
    {
        return "Yagp";
    }

    /**
    * Summary of getInstance
    * @return mixed
    */
    public static function getInstance(): mixed
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
            if (!self::$_instance->getFromDB(1)) {
                self::$_instance->getEmpty();
            }
        }
        return self::$_instance;
    }

    /**
     * getConfig
     *
     * @param  mixed $update
     * @return mixed
     */
    public static function getConfig($update = false): mixed
    {
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
    public static function showConfigForm(): bool
    {
        global $DB, $CFG_GLPI;

        $config = new self();
        $config->getFromDB(1);

        $config->showFormHeader(['colspan' => 4]);

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Change ticket solved date to last task end time", "yagp") . "</td><td >";
        Dropdown::showYesNo("ticketsolveddate", $config->fields["ticketsolveddate"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Auto renew tacit contracts", "yagp") . "</td><td >";
        Dropdown::showYesNo("contractrenew", $config->fields["contractrenew"]);
        echo "</td></tr>\n";

        /**** Deprecated
        * echo "<tr class='tab_bg_1'>";
        * echo "<td >".__("Fixed Menu", "yagp")."</td><td >";
        * Dropdown::showYesNo("fixedmenu", $config->fields["fixedmenu"]);
        * echo "</td></tr>\n";
        ****/

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Go to ticket", "yagp") . "</td><td >";
        Dropdown::showYesNo("gototicket", $config->fields["gototicket"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Block opening date", "yagp") . "</td><td >";
        Dropdown::showYesNo("blockdate", $config->fields["blockdate"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Replace ticket requester (mailcollector)", "yagp") . "</td><td >";
        Dropdown::showYesNo("findrequest", $config->fields["findrequest"]);
        echo "</td></tr>\n";

        if ($config->fields['findrequest']) {
            echo "<tr class='tab_bg_1'>";
            echo "<td >" . __("Tag to search", "yagp") . "</td><td >";
            echo Html::input("requestlabel", ['value' => $config->fields["requestlabel"]]);
            echo "</td></tr>\n";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Change default minimum validation required", "yagp") . "</td><td >";
        Dropdown::showYesNo("change_df_min_val", $config->fields["change_df_min_val"]);
        echo "</td></tr>\n";

        if ($config->fields['change_df_min_val']) {
            echo "<tr class='tab_bg_1'>";
            echo "<td >" . __("Default minimum validation required", "yagp") . "</td><td >";
            $possible_values = [];
            $possible_values[0] = "0%";
            $possible_values[50] = "50%";
            $possible_values[100] = "100%";
            Dropdown::showFromArray('df_min_validation', $possible_values, ['value' => $config->fields["df_min_validation"]]);
            echo "</td></tr>\n";
        }

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Enable re-categorization tracking", "yagp") . "</td><td >";
        Dropdown::showYesNo("recategorization", $config->fields["recategorization"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Hide historical tab to post-only users", "yagp") . "</td><td >";
        Dropdown::showYesNo("hide_historical", $config->fields["hide_historical"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Enhance Private task/followup view", "yagp") . "</td><td >";
        Dropdown::showYesNo("private_view", $config->fields["private_view"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Enable quick transfer for tickets", "yagp") . "</td><td >";
        Dropdown::showYesNo("quick_transfer", $config->fields["quick_transfer"]);
        echo "</td></tr>\n";

        echo "<tr class='tab_bg_1'>";
        echo "<td >" . __("Auto-closing of rejected tickets", "yagp") . "</td><td >";
        Dropdown::showYesNo("autoclose_rejected_tickets", $config->fields["autoclose_rejected_tickets"]);
        echo "</td></tr>\n";

        if (
            $config->fields['autoclose_rejected_tickets'] == 1
            && $config->fields['solutiontypes_id_rejected'] > 0
        ) {
            $iterator = $DB->request([
                'FROM'  => SolutionTemplate::getTable(),
                'WHERE' => ['solutiontypes_id' => $config->fields['solutiontypes_id_rejected']]
            ]);
            if (count($iterator) == 0) {
                echo "<tr class='tab_bg_1'>";
                echo "<td colspan='2'><span class='text-warning'>";
                echo __("There are no templates for resolving rejected tickets.", "yagp");
                echo "</span><a href='/front/solutiontemplate.php' class='ms-2'>";
                echo __("Go to configuration", "yagp");
                echo "</a></td></tr>\n";
            }
        }

        $config->showFormButtons(['candel' => false]);

        return false;
    }

    /**
     * getTabNameForItem
     *
     * @param  mixed $item
     * @param  mixed $withtemplate
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        global $LANG;

        if ($item->getType() == 'Config') {
            return "Yagp";
        }
        return '';
    }

    /**
     * displayTabContentForItem
     *
     * @param  mixed $item
     * @param  mixed $tabnum
     * @param  mixed $withtemplate
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item->getType() == 'Config') {
            self::showConfigForm($item);
        }
        return true;
    }

    /**
     * install
     *
     * @param  mixed $migration
     * @return void
     */
    public static function install(Migration $migration): void
    {
        global $DB;

        $default_charset    = DBConnection::getDefaultCharset();
        $default_collation  = DBConnection::getDefaultCollation();
        $default_key_sign   = DBConnection::getDefaultPrimaryKeySignOption();

        $table  = self::getTable();
        $config = new self();

        if (!$DB->tableExists($table) && !$DB->tableExists("glpi_plugin_yagp_config")) {
            $migration->displayMessage("Installing $table");
           //Install
            $query = "CREATE TABLE `$table` (
				`id` INT {$default_key_sign} NOT NULL AUTO_INCREMENT,
                `ticketsolveddate` TINYINT(1) NOT NULL DEFAULT '0',
                `contractrenew` TINYINT(1) NOT NULL DEFAULT '0',
                `gototicket` TINYINT(1) NOT NULL DEFAULT '0',
                `blockdate` TINYINT(1) NOT NULL DEFAULT '0',
                `findrequest` TINYINT(1) NOT NULL DEFAULT '0',
                `requestlabel` VARCHAR(255) DEFAULT NULL,
                `change_df_min_val` TINYINT(1) NOT NULL DEFAULT '0',
                `df_min_validation` INT(11) NOT NULL DEFAULT '0',
                `recategorization` TINYINT(1) NOT NULL DEFAULT '0',
                `hide_historical` TINYINT(1) NOT NULL DEFAULT '0',
                `private_view` TINYINT(1) NOT NULL DEFAULT '0',
                `quick_transfer` TINYINT(1) NOT NULL DEFAULT '0',
                `autoclose_rejected_tickets` TINYINT(1) NOT NULL DEFAULT '0',
                `solutiontypes_id_rejected` INT {$default_key_sign} NOT NULL DEFAULT '0',
                PRIMARY KEY  (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
            COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            $DB->query($query) or die($DB->error());
            $config->add(['id' => 1]);
        } else {
            $migration->addField($table, 'gototicket', 'boolean');
            $migration->addField($table, 'blockdate', 'boolean');
            $migration->addField($table, 'findrequest', 'boolean');
            $migration->addField($table, 'requestlabel', 'string');
            $migration->addField($table, 'change_df_min_val', 'boolean');
            $migration->addField($table, 'df_min_validation', 'int');
            $migration->addField($table, 'recategorization', 'boolean');
            $migration->addField($table, 'hide_historical', 'boolean');
            $migration->addField($table, 'private_view', 'boolean');
           // * 2.2.0
            $migration->addField($table, 'quick_transfer', 'boolean', ['value' => 0]);
            $migration->addField($table, 'autoclose_rejected_tickets', 'boolean', ['value' => 0]);
            $migration->addField($table, 'solutiontypes_id_rejected', 'int', ['value' => 0]);
            $migration->migrationOneTable($table);
        }
    }
}
