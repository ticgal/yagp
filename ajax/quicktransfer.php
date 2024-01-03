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

include("../../../inc/includes.php");
header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

$plugin = new Plugin();
if (!$plugin->isInstalled('yagp') || !$plugin->isActivated('yagp')) {
    Html::displayNotFoundError();
}

Session::checkLoginUser();

$_REQUEST['_in_modal'] = 1;
Html::header('yagp');

$transfer = new PluginYagpTransfer();
if (isset($_POST["id"]) && ($_POST["id"] > 0)) {
    $transfer->showForm(
        1,
        [
            'target'        => Plugin::getWebDir('yagp') . "/front/transfer.form.php",
            'display'       => false,
            'transferlist'  => PluginYagpTransfer::getCompleteTransferOptions()
        ]
    );
}

if (isset($_GET['itemtype']) && isset($_GET['items_id'])) {
    $itemtype = $_GET['itemtype'];
    $id = $_GET['items_id'];

    $transferlist = [];
    $transferlist[$itemtype][$id] = $id;

    $config = PluginYagpConfig::getInstance();
    $item = new $itemtype();
    $item->getFromDB($id);
    if (
        isset($config->fields['autotransfer'])
        && $config->fields['autotransfer'] == 1
        && $item->fields['entities_id'] != $config->fields['transfer_entity']
    ) {
        $glpitransfer = new Transfer();
        $glpitransfer->moveItems(
            $transferlist,
            $config->fields['transfer_entity'],
            PluginYagpTransfer::getCompleteTransferOptions()
        );

        $msg = __("Ticket transferred to %s", 'yagp');
        $sprintf = sprintf(
            $msg,
            Dropdown::getDropdownName('glpi_entities', $config->fields['transfer_entity'])
        );

        echo "<div class='d-flex w-100 justify-content-center align-items-center'>";
        echo "<div class='alert alert-info mt-4'>";
        echo "<h3>" . $sprintf . "</h3>";
        echo "<span class='text-muted'>" . __('You can close this window', 'yagp') . "</span>";
        echo "</div>";
        echo "</div>";
    } else {
        $transfer->showTransferList($transferlist);
    }
}
