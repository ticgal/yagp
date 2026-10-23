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

    // Deprecated in GLPI 11 (native behavior available in core).
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
        // Deprecated in GLPI 11:
        // "See group tickets only" is now a native GLPI capability.
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
