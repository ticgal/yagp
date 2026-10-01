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

use Glpi\Plugin\Hooks;
use GlpiPlugin\Yagp\Config;
use GlpiPlugin\Yagp\Postshowitem;
use GlpiPlugin\Yagp\Ticket as PluginTicket;

define('PLUGIN_YAGP_VERSION', '4.0.0-beta.1');
// Minimal GLPI version, inclusive
define("PLUGIN_YAGP_MIN_GLPI", "12.0.0");
// Maximum GLPI version, exclusive
define("PLUGIN_YAGP_MAX_GLPI", "12.1.0");

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
        'author'            => '<a href="https://tic.gal">TICGAL</a>',
        'homepage'          => 'https://tic.gal/yagp',
        'license'           => 'GPLv3+',
        'minGlpiVersion'    => PLUGIN_YAGP_MIN_GLPI,
        'requirements'      => [
            'glpi'   => [
                'min' => PLUGIN_YAGP_MIN_GLPI,
                'max' => PLUGIN_YAGP_MAX_GLPI,
            ],
        ],
    ];
}

/**
 * plugin_init_yagp
 *
 * @return void
 */
function plugin_init_yagp(): void
{
    /** @var array $PLUGIN_HOOKS */
    global $PLUGIN_HOOKS;

    if (Session::haveRightsOr("config", [READ, UPDATE])) {
        Plugin::registerClass(Config::class, ['addtabon' => \Config::class]);
        $PLUGIN_HOOKS['config_page']['yagp'] = 'front/config.form.php';
    }

    $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['yagp'] = [
        Config::class  => 'plugin_yagp_updateitem',
        TicketSatisfaction::class  => [PluginTicket::class, 'plugin_yagp_preItemUpdate'],
    ];

    $plugin = new Plugin();
    if ($plugin->isActivated('yagp')) {
        $config = Config::getInstance();
        if ($config->fields['gototicket']) {
            if (Session::getCurrentInterface() != "helpdesk") {
                $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['yagp'][] = 'gototicket.js';
            }
        }

        if ($config->fields['blockdate']) {
            $PLUGIN_HOOKS[Hooks::ADD_JAVASCRIPT]['yagp'][] = 'blockdate.js';
        }

        if ($config->fields['findrequest']) {
            if (
                !is_null($config->fields['requestlabel'])
                && $config->fields['requestlabel'] != ""
            ) {
                $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['yagp'] = [
                    Ticket::class => [
                        PluginTicket::class, 'preAddTicket',
                    ],
                ];
            }
        }

        $PLUGIN_HOOKS[Hooks::PRE_SHOW_TAB]['yagp'] = 'plugin_yagp_pre_show_tab';

        if ($config->fields['recategorization'] || $config->fields['autoclose_rejected_tickets']) {
            $PLUGIN_HOOKS[Hooks::ITEM_UPDATE]['yagp'] = [
                Ticket::class => [
                    PluginTicket::class, 'pluginYagpItemUpdate',
                ],
            ];
            $PLUGIN_HOOKS[Hooks::POST_ITEM_FORM]['yagp'] = [
                PluginTicket::class, 'plugin_yagp_postItemForm',
            ];
        }

        if ($config->fields['hide_historical']) {
            $PLUGIN_HOOKS[Hooks::PRE_SHOW_ITEM]['yagp'] = [
                PluginTicket::class, 'plugin_yagp_preShowItem',
            ];
        }

        if (
            $config->fields['private_view']
            || $config->fields['quick_transfer']
            || $config->fields['modal_satisfaction']
        ) {
            $PLUGIN_HOOKS[Hooks::POST_SHOW_ITEM]['yagp'] = [
                Postshowitem::class, 'pluginYagpPostShowItem',
            ];
        }

        if ($config->fields['autoclose_rejected_tickets']) {
            $PLUGIN_HOOKS[Hooks::ITEM_ADD]['yagp'][ITILFollowup::class] = [
                PluginTicket::class, 'pluginYagpItemAdd',
            ];
        }

        if (!empty($config->fields['solutiontypes'])) {
            $PLUGIN_HOOKS[Hooks::ITEM_ADD]['yagp'][ITILSolution::class] = [
                PluginTicket::class, 'pluginYagpItemAdd',
            ];
        }

        if ($config->fields['observers_affect_status']) {
            $PLUGIN_HOOKS[Hooks::PRE_ITEM_ADD]['yagp'][ITILFollowup::class] = [
                PluginTicket::class, 'preItemAdd',
            ];
        }
    }
}
