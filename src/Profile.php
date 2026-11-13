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

namespace GlpiPlugin\Yagp;

use CommonGLPI;
use Migration;
use ProfileRight;
use Session;
use Toolbox;

/**
 * "See group tickets only" right (2.3.0), removed in 3.0.1.
 *
 * Core has no equivalent: "View new tickets" (READNEWTICKET) is not limited to the
 * user's groups. Only kept to warn about profiles that relied on the legacy right,
 * to clean it up on uninstall and for callers of the old public API.
 */
class Profile extends CommonGLPI
{
    public static string $rightname = 'profile';

    /** Profile right created by YAGP 2.3.0 to 3.0.0 */
    private const LEGACY_RIGHT = 'plugin_yagp_tickets';

    /**
     * @deprecated 3.0.1 "See group tickets only" was removed
     *
     * @return array
     */
    public static function getGeneralRights(): array
    {
        return [];
    }

    /**
     * @deprecated 3.0.1 "See group tickets only" was removed
     *
     * @return bool
     */
    public static function getAllocatorPermission(): bool
    {
        Toolbox::deprecated('"See group tickets only" was removed in YAGP 3.0.1');

        return false;
    }

    /**
     * @deprecated 3.0.1 "See group tickets only" was removed: this no longer restricts anything
     *
     * @param \Ticket $item
     *
     * @return bool
     */
    public static function checkAllocatorAccess(\Ticket $item): bool
    {
        Toolbox::deprecated('"See group tickets only" was removed in YAGP 3.0.1, this check always allows');

        return true;
    }

    /**
     * Warn about profiles that still hold the "See group tickets only" right.
     *
     * The rows are kept on purpose, so the administrator can still find out which
     * profiles relied on the restriction. They are removed on uninstall.
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
            'SELECT'     => [\Profile::getTable() . '.name'],
            'FROM'       => ProfileRight::getTable(),
            'INNER JOIN' => [
                \Profile::getTable() => [
                    'ON' => [
                        ProfileRight::getTable() => 'profiles_id',
                        \Profile::getTable()     => 'id',
                    ],
                ],
            ],
            'WHERE'      => [
                ProfileRight::getTable() . '.name'   => self::LEGACY_RIGHT,
                ProfileRight::getTable() . '.rights' => ['>', 0],
            ],
            'ORDER'      => \Profile::getTable() . '.name',
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
        if (countElementsInTable(ProfileRight::getTable(), ['name' => self::LEGACY_RIGHT]) > 0) {
            $migration->displayMessage("Removing profile right " . self::LEGACY_RIGHT);
            ProfileRight::deleteProfileRights([self::LEGACY_RIGHT]);
        }
    }
}
