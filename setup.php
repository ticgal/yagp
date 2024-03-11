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

use Glpi\Plugin\Hooks;

define('PLUGIN_YAGP_VERSION', '2.3.1');
// Minimal GLPI version, inclusive
define("PLUGIN_YAGP_MIN_GLPI", "10.0");
// Maximum GLPI version, exclusive
define("PLUGIN_YAGP_MAX_GLPI", "11.0");

/**
 * plugin_version_yagp
 *
 * @return array
 */
function plugin_version_yagp(): array
{
    return [
        'name'              => 'YAGP',
        'version'           => PLUGIN_YAGP_VERSION,
        'author'            => '<a href="https://tic.gal">TICgal</a>',
        'homepage'          => 'https://tic.gal/yagp',
        'license'           => 'GPLv3+',
        'minGlpiVersion'    => PLUGIN_YAGP_MIN_GLPI,
        'requirements'      => [
            'glpi'   => [
                'min' => PLUGIN_YAGP_MIN_GLPI,
                'max' => PLUGIN_YAGP_MAX_GLPI,
            ]
        ]
    ];
}

/**
 * Check plugin's config before activation
 */
function plugin_yagp_check_config($verbose = false): bool
{
    return true;
}

/**
 * plugin_init_yagp
 *
 * @return void
 */
function plugin_init_yagp(): void
{
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS[Hooks::CSRF_COMPLIANT]['yagp'] = true;

    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass('PluginYagpConfig', ['addtabon' => 'Config']);
        $PLUGIN_HOOKS['config_page']['yagp'] = 'front/config.form.php';
    }

    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['yagp'] = [
        PluginYagpConfig::class  => 'plugin_yagp_updateitem'
    ];

    $plugin = new Plugin();
    if ($plugin->isActivated('yagp')) {
        Plugin::registerClass(PluginYagpTransfer::class);

        Plugin::registerClass('PluginYagpProfile', ['addtabon' => 'Profile']);

        $config = PluginYagpConfig::getInstance();
        /**** Deprecated
        *   if ($config->fields['fixedmenu']) {
        *      $PLUGIN_HOOKS['add_css']['yagp']='fixedmenu.css';
        }****/
        if ($config->fields['gototicket']) {
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['yagp'][] = 'js/gototicket.js';
        }

        if ($config->fields['blockdate']) {
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['yagp'][] = 'js/blockdate.js';
        }

        if ($config->fields['findrequest']) {
            if (
                !is_null($config->fields['requestlabel'])
                && $config->fields['requestlabel'] != ""
            ) {
                $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['yagp'] = [
                    Ticket::class => [
                        PluginYagpTicket::class, 'preAddTicket'
                    ]
                ];
            }
        }

        if ($config->fields['change_df_min_val']) {
            $PLUGIN_HOOKS[Hooks::PRE_SHOW_TAB]['yagp'] = [
                PluginYagpPreshowtab::class, "preShowTab"
            ];
        }

        if ($config->fields['recategorization'] || $config->fields['autoclose_rejected_tickets']) {
            $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['yagp'] = [
                Ticket::class => [
                    PluginYagpTicket::class, 'pluginYagpItemUpdate'
                ],
            ];
            $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['yagp'] = [
                PluginYagpTicket::class, 'plugin_yagp_postItemForm'
            ];
        }

        if ($config->fields['hide_historical']) {
            $PLUGIN_HOOKS[Hooks::PRE_SHOW_ITEM]['yagp'] = [
                PluginYagpTicket::class, 'plugin_yagp_preShowItem'
            ];
            $PLUGIN_HOOKS[Hooks::PRE_SHOW_TAB]['yagp'] = [
                PluginYagpPreshowtab::class, "plugin_yagp_preShowTab"
            ];
        }

        if ($config->fields['private_view'] || $config->fields['quick_transfer']) {
            $PLUGIN_HOOKS[Hooks::POST_SHOW_ITEM]['yagp'] = [
                PluginYagpPostshowitem::class, 'pluginYagpPostShowItem'
            ];
        }

        if ($config->fields['autoclose_rejected_tickets']) {
            $PLUGIN_HOOKS[Hooks::ITEM_ADD]['yagp'][ITILFollowup::class] = [
                PluginYagpTicket::class, 'pluginYagpItemAdd'
            ];
        }

        if (!empty($config->fields['solutiontypes'])) {
            $PLUGIN_HOOKS[Hooks::ITEM_ADD]['yagp'][ITILSolution::class] = [
                PluginYagpTicket::class, 'pluginYagpItemAdd'
            ];
        }

        $PLUGIN_HOOKS[Hooks::ITEM_CAN]['yagp'][Ticket::class] = [
            PluginYagpProfile::class, 'checkAllocatorAccess'
        ];

        $PLUGIN_HOOKS['add_default_join']['yagp'] = "Plugin_Yagp_addDefaultJoin";
        $PLUGIN_HOOKS['add_default_where']['yagp'] = "Plugin_Yagp_addDefaultWhere";
    }
}
