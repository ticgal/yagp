<?php

/**
 * -------------------------------------------------------------------------
 * YAGP plugin for GLPI
 * Copyright (C) 2019-2024 by the TICgal Team.
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
 * --------------------------------------------------------------------------
 * @package   YAGP
 * @author    the TICgal team
 * @copyright Copyright (c) 2019-2024 TICgal team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://tic.gal/en/project/yagp-yet-another-glpi-plugin/
 * @since     2019
 * ----------------------------------------------------------------------
 */

/**
 * Install all necessary elements for the plugin
 *
 * @return boolean True if success
 */
function plugin_yagp_install(): bool
{
    $migration = new Migration(PLUGIN_YAGP_VERSION);

   // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
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
function plugin_yagp_uninstall(): bool
{
    $migration = new Migration(PLUGIN_YAGP_VERSION);

   // Parse inc directory
    foreach (glob(dirname(__FILE__) . '/inc/*') as $filepath) {
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

/**
 * plugin_yagp_updateitem
 *
 * @param  mixed $item
 * @return void
 */
function plugin_yagp_updateitem(CommonDBTM $item): void
{
    if ($item::getType() == "PluginYagpConfig") {
        $input = $item->input;
        if ($input["ticketsolveddate"] == 1) {
            Crontask::Register("PluginYagpTicketsolveddate", 'changeDate', HOUR_TIMESTAMP, [
            'state' => 1,
            'mode'  => CronTask::MODE_EXTERNAL
            ]);
        } elseif ($input["ticketsolveddate"] == 0) {
            Crontask::Unregister("YagpTicketsolveddate");
        }

        /* Deprecated
        if ($input["contractrenew"] == 1) {
            Crontask::Register("PluginYagpContractrenew", 'renewContract', DAY_TIMESTAMP, [
            'state' => 0,
            'mode'  => CronTask::MODE_EXTERNAL
            ]);
        } elseif ($input["contractrenew"] == 0) {
            Crontask::Unregister("YagpContractrenew");
        }
        */
    }
}

/**
 * plugin_yagp_getAddSearchOptions
 *
 * @param  mixed $itemtype
 * @return array
 */
function plugin_yagp_getAddSearchOptions($itemtype): array
{
    $config = PluginYagpConfig::getInstance();

    $sopt = [];
    if ($config->fields['recategorization']) {
        switch ($itemtype) {
            case "Ticket":
                $sopt['yagp'] = ['name' => 'YAGP'];

                $sopt[9021321] = [
                    'table'                 => PluginYagpTicket::getTable(),
                    'field'                 => 'is_recategorized',
                    'name'                  => __('Recategorized', 'yagp'),
                    'searchtype'            => ['equals', 'notequals'],
                    'massiveaction'         => false,
                    'searchequalsonfield'   => true,
                    'datatype'              => 'specific',
                    'joinparams' => [
                        'jointype'          => 'child',
                        'linkfield'         => 'tickets_id'
                    ]
                ];

                $sopt[9021322] = [
                    'table'                 => PluginYagpTicket::getTable(),
                    'field'                 => 'plugin_yagp_itilcategories_id',
                    'name'                  => __('Initial category', 'yagp'),
                    'searchtype'            => ['equals', 'notequals'],
                    'massiveaction'         => false,
                    'searchequalsonfield'   => true,
                    'datatype'              => 'specific',
                    'joinparams' => [
                        'jointype'          => 'child',
                        'linkfield'         => 'tickets_id'
                    ]
                ];
        }
    }
    return $sopt;
}

/**
 * Plugin_Yagp_addDefaultJoin
 *
 * @param  mixed $in
 * @return array
 */
function Plugin_Yagp_addDefaultJoin($in): array
{
    list($itemtype, $out) = $in;

    if (!PluginYagpProfile::getAllocatorPermission()) {
        return [$itemtype, $out];
    }

    if (isset($in[0]) && $in[0] == Ticket::class && isset($_SERVER['REQUEST_URI'])) {
        if (
            isset($in[1]) &&
            (preg_match('/\/front\/ticket/', $_SERVER['REQUEST_URI']) ||
            preg_match('/\/ajax\/search.*itemtype=Ticket/', $_SERVER['REQUEST_URI']))
        ) {
            $new_condition = PluginYagpProfile::getAllocatorSQLTickets();
            $out .= " INNER JOIN $new_condition `yagp` ON `yagp`.`tickets_id` = `glpi_tickets`.`id`";
        }
    }

    return [$itemtype, $out];
}

/**
 * Plugin_Yagp_addDefaultWhere
 *
 * @param  array $in
 * @return array
 */
function Plugin_Yagp_addDefaultWhere(array $in): array
{
    if (!PluginYagpProfile::getAllocatorPermission()) {
        return $in;
    }

    if (isset($in[0]) && $in[0] == Ticket::class && isset($_SERVER['REQUEST_URI'])) {
        if (
            isset($in[1]) &&
            (preg_match('/\/front\/ticket/', $_SERVER['REQUEST_URI']) ||
            preg_match('/\/ajax\/search.*itemtype=Ticket/', $_SERVER['REQUEST_URI']))
        ) {
            $condition = "`glpi_tickets`.`status`='1'";
            $new_condition = "(`glpi_tickets`.`status`='1' AND `yagp`.`assoc` IS NOT NULL)";
            // replace condition
            $in[1] = str_replace($condition, $new_condition, $in[1]);
            $in[1] .= " AND `yagp`.`assoc` IS NOT NULL";
        }
    }

    return $in;
}
