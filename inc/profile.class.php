<?php

/**
 * -------------------------------------------------------------------------
 * YAGP plugin for GLPI
 * Copyright (C) 2019-2026 by the TICgal Team.
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
 * @copyright Copyright (c) 2026 TICGAL team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://www.tic.gal
 * @since     2019
 * -------------------------------------------------------------------------
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PluginYagpProfile extends Profile
{
    public static $rightname = "profile";

    // "See group tickets only" was removed in 3.0.1. Core has no equivalent:
    // "View new tickets" (READNEWTICKET) is not limited to the user's groups.
    // public const SEE_GROUP_TICKETS_ONLY = 1;

    /**
     * {@inheritdoc}
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string|array
    {
        // Deprecated in GLPI 11:
        // do not add YAGP profile tab.
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): bool
    {
        // Deprecated in GLPI 11:
        // do not render YAGP profile tab content.
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function showForm($ID, $options = []): bool
    {
        // Deprecated in GLPI 11:
        // remove YAGP profile rights UI (See group tickets only).
        return false;
    }

    /**
     * getGeneralRights
     *
     * @return array
     */
    public static function getGeneralRights(): array
    {
        // "See group tickets only" was removed in 3.0.1 (see install() for the upgrade warning).
        /*
        $crud = [
            self::SEE_GROUP_TICKETS_ONLY => __("See group tickets only", 'yagp'),
        ];

        $rights = [
            'yagp' => [
                'rights'    => $crud,
                'itemtype'  => self::getType(),
                'label'     => __("Ticket"),
                'field'     => 'plugin_yagp_tickets',
            ],
        ];

        return $rights;
        */
        return [];
    }

    /**
     * Warn about profiles that still hold the "See group tickets only" right.
     *
     * The restriction stopped being applied in 3.0.1. The right rows are kept on
     * purpose, so the administrator can still find out which profiles relied on it.
     *
     * @param Migration $migration
     *
     * @return void
     */
    public static function install(Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        $iterator = $DB->request([
            'SELECT'     => [Profile::getTable() . '.name'],
            'FROM'       => ProfileRight::getTable(),
            'INNER JOIN' => [
                Profile::getTable() => [
                    'ON' => [
                        ProfileRight::getTable() => 'profiles_id',
                        Profile::getTable()      => 'id',
                    ],
                ],
            ],
            'WHERE'      => [
                ProfileRight::getTable() . '.name'   => 'plugin_yagp_tickets',
                ProfileRight::getTable() . '.rights' => ['>', 0],
            ],
            'ORDER'      => Profile::getTable() . '.name',
        ]);

        $profiles = [];
        foreach ($iterator as $data) {
            $profiles[] = $data['name'];
        }
        if ($profiles === []) {
            return;
        }

        $message = sprintf(
            __('The "See group tickets only" restriction is no longer applied since YAGP 3.0.1. Users of these profiles can see every new ticket of their entities: %s. Review their ticket rights.', 'yagp'),
            implode(', ', $profiles),
        );
        // Migration messages only reach the console and the migration log,
        // so the warning is also queued for the web interface.
        $migration->addWarningMessage($message);
        Session::addMessageAfterRedirect(htmlescape($message), false, WARNING);
    }

    /**
     * @param Migration $migration
     *
     * @return void
     */
    public static function uninstall(Migration $migration): void
    {
        // getGeneralRights() is empty since 3.0.1, but installs from older
        // versions still hold the "See group tickets only" right.
        $migration->displayMessage("Removing profile rights");
        ProfileRight::deleteProfileRights(['plugin_yagp_tickets']);
    }
}
