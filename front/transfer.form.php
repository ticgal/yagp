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

include('../../../inc/includes.php');

$plugin = new Plugin();
if (!$plugin->isInstalled('yagp') || !$plugin->isActivated('yagp')) {
    Html::displayNotFoundError();
}

Session::checkRight("transfer", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$transfer = new Transfer();

$_REQUEST['_in_modal'] = 1;
Html::header('yagp');

if (isset($_POST['transfer'])) {
    if (isset($_POST['transferlist'])) {
        if (!Session::haveAccessToEntity($_POST['to_entity'])) {
            Html::displayRightError();
        }

        $default = PluginYagpTransfer::getCompleteTransferOptions();
        foreach ($default as $k => $v) {
            $_POST[$k] = isset($_POST[$k]) ? $_POST[$k] : $v;
        }

        $transfer->moveItems(
            json_decode(stripslashes($_POST['transferlist']), true),
            $_POST['to_entity'],
            $_POST
        );

        $entity = new Entity();
        $entity->getFromDB($_POST['to_entity']);

        $msg = __("Ticket transferred to %s", 'yagp');
        $sprintf = sprintf(
            $msg,
            Dropdown::getDropdownName('glpi_entities', $_POST['to_entity'])
        );

        echo "<div class='d-flex w-100 justify-content-center align-items-center'>";
        echo "<div class='alert alert-info mt-4'>";
        echo "<h3>" . $sprintf . "</h3>";
        echo "<span class='text-muted'>" . __('You can close this window', 'yagp') . "</span>";
        echo "</div>";
        echo "</div>";

        exit();
    }
}
