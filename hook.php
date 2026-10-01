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

use GlpiPlugin\Yagp\Config;
use GlpiPlugin\Yagp\Preshowtab;
use GlpiPlugin\Yagp\Profile;
use GlpiPlugin\Yagp\Ticket as PluginTicket;
use GlpiPlugin\Yagp\Ticketsolveddate;

/**
 * Install all necessary elements for the plugin
 *
 * @return boolean True if success
 */
function plugin_yagp_install(): bool
{
    $migration = new Migration(PLUGIN_YAGP_VERSION);

    Config::install($migration);
    PluginTicket::install($migration);
    Ticketsolveddate::install($migration);
    Profile::install($migration);

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

    Profile::uninstall($migration);
    PluginTicket::uninstall($migration);
    Config::uninstall($migration);
    CronTask::unregister('yagp');

    return true;
}

/**
 * plugin_yagp_updateitem
 *
 * @param  CommonDBTM $item
 * @return void
 */
function plugin_yagp_updateitem(CommonDBTM $item): void
{
    if ($item instanceof Config && isset($item->input['ticketsolveddate'])) {
        if ($item->input['ticketsolveddate'] == 1) {
            Ticketsolveddate::registerCronTask();
        } elseif ($item->input['ticketsolveddate'] == 0) {
            Ticketsolveddate::unregisterCronTask();
        }
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
    $config = Config::getInstance();

    $sopt = [];
    if ($config->fields['recategorization']) {
        switch ($itemtype) {
            case "Ticket":
                $sopt['yagp'] = ['name' => 'YAGP'];

                $sopt[9021321] = [
                    'table'                 => PluginTicket::getTable(),
                    'field'                 => 'is_recategorized',
                    'name'                  => __('Recategorized', 'yagp'),
                    'searchtype'            => ['equals', 'notequals'],
                    'massiveaction'         => false,
                    'searchequalsonfield'   => true,
                    'datatype'              => 'specific',
                    'joinparams' => [
                        'jointype'          => 'child',
                        'linkfield'         => 'tickets_id',
                    ],
                ];

                $sopt[9021322] = [
                    'table'                 => PluginTicket::getTable(),
                    'field'                 => 'plugin_yagp_itilcategories_id',
                    'name'                  => __('Initial category', 'yagp'),
                    'searchtype'            => ['equals', 'notequals'],
                    'massiveaction'         => false,
                    'searchequalsonfield'   => true,
                    'datatype'              => 'specific',
                    'joinparams' => [
                        'jointype'          => 'child',
                        'linkfield'         => 'tickets_id',
                    ],
                ];
        }
    }
    return $sopt;
}

/**
 * @param array $params
 *
 * @return void
 */
function plugin_yagp_pre_show_tab(array $params): void
{
    $config = Config::getInstance();
    if ($config->fields['hide_historical']) {
        Preshowtab::plugin_yagp_preShowTab($params);
    }
}
