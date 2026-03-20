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
     * showWarning
     *
     * @param  string $event - Events:
     * - no_group - User has the allocator profile and is not in any group
     * @return void
     */
    public static function showWarning(string $event): void
    {
        // Deprecated in GLPI 11 with allocator filter removal.
        /*
        // save messages
        $msg_copy = $_SESSION['MESSAGE_AFTER_REDIRECT'];
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = [];

        $msg = "";
        switch ($event) {
            case 'no_group':
                $msg = "YAGP - " . __("See group tickets only", 'yagp') . " " . __("permission") . ": ";
                $msg .= __("You are not in any group", 'yagp');
                break;
        }

        // show message
        Session::addMessageAfterRedirect($msg, false, WARNING);
        Html::displayMessageAfterRedirect();

        // restore messages
        $_SESSION['MESSAGE_AFTER_REDIRECT'] = $msg_copy;
        */
    }

    /**
     * getAllocatorPermission
     *
     * @return bool
     */
    public static function getAllocatorPermission(): bool
    {
        // Deprecated in GLPI 11:
        // use GLPI native permissioning instead of plugin-specific right.
        /*
        if (
            !Session::haveRight('ticket', Ticket::READALL) &&
            Session::haveRight('ticket', Ticket::ASSIGN) &&
            Session::haveRight('plugin_yagp_tickets', PluginYagpProfile::SEE_GROUP_TICKETS_ONLY)
        ) {
            return true;
        }
        */
        return false;
    }

    /**
     * getAllocatorSQLTickets
     *
     * @return string
     */
    public static function getAllocatorSQLTickets(): string
    {
        // Deprecated in GLPI 11 with allocator filter removal.
        /*
        $group_user = new Group_User();
        $grouplist = array_column($group_user->find(['users_id' => Session::getLoginUserID()]), 'groups_id');

        if (!empty($grouplist)) {
            $groups = implode(',', $grouplist);
        } else {
            $groups = 0;
            if ($_SERVER['REQUEST_URI'] == '/front/ticket.php') {
                self::showWarning('no_group');
            }
        }

        // Tickets related to the user
        $sql = "(";
        $sql .= "SELECT `tickets_id`, `groups_id` AS `assigned`, 'Group' AS `assoc` FROM `glpi_groups_tickets` ";
        $sql .= "WHERE `groups_id` IN (" . $groups . ")";
        $sql .= " UNION ALL ";
        $sql .= "SELECT `tickets_id`, `users_id` AS `assigned`, 'User' AS `assoc`";
        $sql .= "FROM `glpi_tickets_users` WHERE `users_id` = '" . Session::getLoginUserID() . "'";
        $sql .= " UNION ALL ";
        $sql .= "SELECT `id` AS `tickets_id`, `users_id_recipient` AS `assigned`, 'Owner' AS `assoc` ";
        $sql .= "FROM `glpi_tickets` WHERE `users_id_recipient` = '" . Session::getLoginUserID() . "'";
        $sql .= ")";

        return $sql;
        */
        return '';
    }

    /**
     * @param Ticket $item
     *
     * @return bool
     */
    public static function checkAllocatorAccess(Ticket $item): bool
    {
        // Deprecated in GLPI 11:
        // access control is delegated to native GLPI permissions.
        /*
        if (self::getAllocatorPermission()) {
            $DB = DBConnection::getReadConnection();

            $allocatorSQL = self::getAllocatorSQLTickets();
            if ($items_id = $item->getID()) {
                $query = "SELECT id FROM glpi_tickets INNER JOIN $allocatorSQL `yagp` ";
                $query .= "ON `yagp`.`tickets_id` = `glpi_tickets`.`id` ";
                $query .= "WHERE `glpi_tickets`.`id` = $items_id";
                $access = count($DB->request($query));
                if ($access == 0) {
                    $item->right = 0;
                }
            }
        }
        */
        return true;
    }

    /**
     * @param Migration $migration
     *
     * @return void
     */
    public static function uninstall(Migration $migration): void
    {
        $migration->displayMessage("Removing profile rights");
        foreach (self::getGeneralRights() as $data) {
            ProfileRight::deleteProfileRights([$data['field']]);
        }
    }
}
