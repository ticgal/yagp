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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginYagpProfile extends Profile
{
    public static $rightname = "profile";

    public const SEE_GROUP_TICKETS_ONLY = 1;

    /**
     * getTabNameForItem
     *
     * @param  mixed $item
     * @param  mixed $withtemplate
     * @return string
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0): string
    {
        switch ($item->getType()) {
            case 'Profile':
                return self::createTabEntry('YAGP');
                break;
        }

        return '';
    }

    /**
     * displayTabContentForItem
     *
     * @param  mixed $item
     * @param  mixed $tabnum
     * @param  mixed $withtemplate
     * @return void
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0): void
    {
        switch ($item->getType()) {
            case 'Profile':
                $profile = new self();
                $profile->showForm($item->getID());
                break;
        }
    }

    /**
     * showForm
     *
     * @param  mixed $profiles_id
     * @param  mixed $options
     * @return void
     */
    public function showForm($profiles_id, $options = []): void
    {
        if (!Session::haveRight("profile", READ)) {
            return;
        }
        $canedit = Session::haveRight("profile", UPDATE);

        $profile = new Profile();
        $profile->getFromDB($profiles_id);

        echo "<form action='" . Profile::getFormUrl() . "' method='post'>";

        $general_rights = self::getGeneralRights();
        $matrix_options = [
            'canedit'       => $canedit,
            'default_class' => 'tab_bg_2',
            'title'         => 'Yet Another GLPI Plugin'
        ];

        $profile->displayRightsChoiceMatrix($general_rights, $matrix_options);
        $profile->showLegend();

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit("<i class='fas fa-save'></i><span>" . _sx('button', 'Save') . "</span>", [
                'class' => 'btn btn-primary mt-2',
                'name'  => 'update'
            ]);
            echo "</div>\n";
            Html::closeForm();
        }
        echo "</div>";
    }

    /**
     * getGeneralRights
     *
     * @return array
     */
    public static function getGeneralRights(): array
    {
        $crud = [
            self::SEE_GROUP_TICKETS_ONLY => __("See group tickets only", 'yagp')
        ];

        $rights = [
            'yagp' => [
                'rights'    => $crud,
                'itemtype'  => self::getType(),
                'label'     => __("Ticket"),
                'field'     => 'plugin_yagp_tickets'
            ]
        ];

        return $rights;
    }

    /**
     * showWarning
     *
     * @param  mixed $event - Events:
     * - no_group - User has the allocator profile and is not in any group
     * @return void
     */
    public static function showWarning(string $event): void
    {
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
    }

    /**
     * getAllocatorPermission
     *
     * @return bool
     */
    public static function getAllocatorPermission(): bool
    {
        if (
            !Session::haveRight('ticket', Ticket::READALL) &&
            Session::haveRight('ticket', Ticket::ASSIGN) &&
            Session::haveRight('plugin_yagp_tickets', PluginYagpProfile::SEE_GROUP_TICKETS_ONLY)
        ) {
            return true;
        }

        return false;
    }

    /**
     * getAllocatorSQLTickets
     *
     * @return string
     */
    public static function getAllocatorSQLTickets(): string
    {
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
    }

    public static function checkAllocatorAccess(Ticket $item): bool
    {
        if (self::getAllocatorPermission()) {
            $DB = DBConnection::getReadConnection();

            $allocatorSQL = self::getAllocatorSQLTickets();
            if (!is_null($item) && $items_id = $item->getID()) {
                $query = "SELECT id FROM glpi_tickets INNER JOIN $allocatorSQL `yagp` ";
                $query .= "ON `yagp`.`tickets_id` = `glpi_tickets`.`id` ";
                $query .= "WHERE `glpi_tickets`.`id` = $items_id";
                $access = count($DB->request($query));
                if ($access == 0) {
                    $item->right = 0;
                }
            }
        }

        return true;
    }

    /**
     * uninstall
     *
     * @return void
     */
    public static function uninstall(): void
    {
        global $DB;

        $table = ProfileRight::getTable();
        $query = "DELETE FROM $table WHERE `name` LIKE '%plugin_yagp%'";
        $DB->query($query) or die($DB->error());
    }
}
