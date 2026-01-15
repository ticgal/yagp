<?php

/**
 * -------------------------------------------------------------------------
 * YAGP plugin for GLPI
 * Copyright (C) 2019-2025 by the TICgal Team.
 * https://tic.gal/en/project/yagp-yet-another-glpi-plugin/
 * -------------------------------------------------------------------------
 * LICENSE
 * This file is part of the YAGP plugin.
 * YAGP plugin is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * YAGP plugin is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with YAGP. If not, see <http://www.gnu.org/licenses/>.
 * -------------------------------------------------------------------------
 * @package   yagp
 * @author    the TICGAL team
 * @copyright Copyright (c) 2025 TICGAL team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://www.tic.gal
 * @since     2019
 * -------------------------------------------------------------------------
 */

use Glpi\Application\View\TemplateRenderer;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PluginYagpConfig extends CommonDBTM
{
    private static $instance = null;

    public static $rightname = 'config';

    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        /** @var \DBmysql $DB */
        global $DB;
        if ($DB->tableExists(self::getTable())) {
            $this->getFromDB(1);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getTypeName($nb = 0): string
    {
        return "YAGP";
    }

    /**
     * getInstance
     *
     * @param  int $n
     * @return PluginYagpConfig
     */
    public static function getInstance(int $n = 1): PluginYagpConfig
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
            if (!self::$instance->getFromDB($n)) {
                self::$instance->getEmpty();
            }
        }
        return self::$instance;
    }

    /**
    * @return boolean
    */
    public static function showConfigForm(): bool
    {
        /** @var \DBmysql $DB */
        global $DB;

        $config = self::getInstance();

        // * Solution types for close tickets automatically
        $solutiontypes = [];
        $used = is_null($config->fields['solutiontypes'])
            ? []
            : importArrayFromDB($config->fields['solutiontypes']);
        $iterator = $DB->request(['FROM' => SolutionType::getTable()]);
        foreach ($iterator as $data) {
            $solutiontypes[$data['id']] = $data['name'];
        }
        $twig = TemplateRenderer::getInstance();
        $twig->getEnvironment()->enableAutoReload();
        $template = "@yagp/config.html.twig";
        echo $twig->render($template, [
            'item'                  => $config,
            'solutiontypes'         => $solutiontypes,
            'used_solutiontypes'    => $used,
            'options' => [
                'full_width' => true,
            ],
        ]);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function prepareInputForUpdate($input): false|array
    {
        if ((!isset($input["solutiontypes"])) || (!is_array($input["solutiontypes"]))) {
            $input["solutiontypes"] = [];
        }
        $input["solutiontypes"] = exportArrayToDB($input["solutiontypes"]);

        return $input;
    }

    /**
     * {@inheritdoc}
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string|array
    {
        if ($item->getType() == 'Config') {
            return "YAGP";
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        if ($item->getType() == 'Config') {
            return self::showConfigForm();
        }

        return false;
    }

    /**
     * addSolutionType
     *
     * @param  PluginYagpConfig $config
     * @return void
     */
    private static function addSolutionType(PluginYagpConfig $config): void
    {
        $solutiontype = new SolutionType();
        if (!$solutiontype->getFromDB($config->fields['solutiontypes_id_rejected'])) {
            $solutionname = "Auto-close rejected tickets";
            $solution_id = $solutiontype->add([
                'name'      => $solutionname,
                'comment'   => __('Solution type for rejected tickets'),
            ]);
            $config->update(['id' => 1, 'solutiontypes_id_rejected' => $solution_id]);
        }
    }

    /**
     * addRequestType
     *
     * @param  PluginYagpConfig $config
     * @return void
     */
    private static function addRequestType(PluginYagpConfig $config): void
    {
        $requesttype = new RequestType();
        if (!$requesttype->getFromDB($config->fields['requesttypes_id_reopen'])) {
            $requestname = "Reopen ticket";
            $request_id = $requesttype->add([
                'name'      => $requestname,
                'comment'   => __('Request type for reopening autoclosed tickets'),
            ]);
            $config->update(['id' => 1, 'requesttypes_id_reopen' => $request_id]);
        }
    }

    /**
     * disableCronTask
     *
     * @return void
     */
    private static function disableCronTask(): void
    {
        CronTask::unregister("YagpContractrenew");
    }

    /**
     * install
     *
     * @param  Migration $migration
     * @return void
     */
    public static function install(Migration $migration): void
    {
        /** @var \DBmysql $DB */
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
                `gototicket` TINYINT(1) NOT NULL DEFAULT '0',
                `blockdate` TINYINT(1) NOT NULL DEFAULT '0',
                `findrequest` TINYINT(1) NOT NULL DEFAULT '0',
                `allow_anonymous_requester` TINYINT(1) NOT NULL DEFAULT '0',
                `requestlabel` VARCHAR(255) DEFAULT NULL,
                `recategorization` TINYINT(1) NOT NULL DEFAULT '0',
                `hide_historical` TINYINT(1) NOT NULL DEFAULT '0',
                `private_view` TINYINT(1) NOT NULL DEFAULT '0',
                `quick_transfer` TINYINT(1) NOT NULL DEFAULT '0',
                `autotransfer` TINYINT(1) NOT NULL DEFAULT '0',
                `transfer_entity` INT {$default_key_sign} NOT NULL DEFAULT '0',
                `autoclose_rejected_tickets` TINYINT(1) NOT NULL DEFAULT '0',
                `solutiontypes` TEXT DEFAULT NULL,
                `solutiontypes_id_rejected` INT {$default_key_sign} NOT NULL DEFAULT '0',
                `requesttypes_id_reopen` INT {$default_key_sign} NOT NULL DEFAULT '0',
                `modal_satisfaction` TINYINT(1) NOT NULL DEFAULT '0',
                `observers_affect_status` TINYINT(1) NOT NULL DEFAULT '0',
                PRIMARY KEY  (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
            COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";

            /**
             * Deprecated
             * `contractrenew` TINYINT(1) NOT NULL DEFAULT '0',
             * `default_satisfaction` INT {$default_key_sign} NOT NULL DEFAULT '3',
             * `change_df_min_val` TINYINT(1) NOT NULL DEFAULT '0',
             * `df_min_validation` INT(11) NOT NULL DEFAULT '0', 
             */

            $DB->doQuery($query);
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
            $migration->addField($table, 'autotransfer', 'int', ['value' => 0]);
            $migration->addField($table, 'transfer_entity', 'int', ['value' => 0]);
            $migration->addField($table, 'autoclose_rejected_tickets', 'boolean', ['value' => 0]);
            $migration->addField($table, 'solutiontypes_id_rejected', 'int', ['value' => 0]);
            $migration->addField($table, 'requesttypes_id_reopen', 'int', ['value' => 0]);
            $migration->addField($table, 'solutiontypes', 'text');
            // * 2.3.0 anonymous requester
            $migration->addField($table, 'allow_anonymous_requester', 'boolean', ['value' => 0]);
            // * 2.4.0
            $migration->addField($table, 'default_satisfaction', 'int', ['value' => 3]);
            // * 2.5.0
            $migration->addField($table, 'modal_satisfaction', 'boolean', ['value' => 0]);
            // * 2.6.0
            $migration->addField($table, 'observers_affect_status', 'boolean', ['value' => 0]);
            // * 3.0.0
            $migration->dropField($table, 'default_satisfaction');
            $migration->dropField($table, 'change_df_min_val');
            $migration->dropField($table, 'df_min_validation');

            $migration->migrationOneTable($table);
        }

        // * 2.2.0
        self::addSolutionType($config);
        self::addRequestType($config);
        self::disableCronTask();
    }
}
