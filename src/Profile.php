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

namespace GlpiPlugin\Yagp;

use CommonGLPI;
use Migration;
use ProfileRight;

/**
 * "See group tickets only" right (2.3.0), removed in 3.0.1: GLPI handles it natively.
 *
 * Only kept to clean up the legacy right and for callers of the old public API.
 */
class Profile extends CommonGLPI
{
    public static string $rightname = 'profile';

    /** Profile right created by YAGP 2.3.0 to 3.0.0 */
    private const LEGACY_RIGHT = 'plugin_yagp_tickets';

    /**
     * @deprecated 3.0.1 "See group tickets only" is native in GLPI
     *
     * @return array
     */
    public static function getGeneralRights(): array
    {
        return [];
    }

    /**
     * @deprecated 3.0.1 "See group tickets only" is native in GLPI
     *
     * @return bool
     */
    public static function getAllocatorPermission(): bool
    {
        return false;
    }

    /**
     * @deprecated 3.0.1 "See group tickets only" is native in GLPI
     *
     * @param \Ticket $item
     *
     * @return bool
     */
    public static function checkAllocatorAccess(\Ticket $item): bool
    {
        return true;
    }

    /**
     * @param Migration $migration
     *
     * @return void
     */
    public static function install(Migration $migration): void
    {
        self::removeLegacyRight($migration);
    }

    /**
     * @param Migration $migration
     *
     * @return void
     */
    public static function uninstall(Migration $migration): void
    {
        self::removeLegacyRight($migration);
    }

    /**
     * @param Migration $migration
     *
     * @return void
     */
    private static function removeLegacyRight(Migration $migration): void
    {
        if (countElementsInTable(ProfileRight::getTable(), ['name' => self::LEGACY_RIGHT]) > 0) {
            $migration->displayMessage("Removing profile right " . self::LEGACY_RIGHT);
            ProfileRight::deleteProfileRights([self::LEGACY_RIGHT]);
        }
    }
}
