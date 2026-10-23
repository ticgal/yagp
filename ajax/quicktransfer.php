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

use Glpi\Exception\Http\AccessDeniedHttpException;
use Glpi\Exception\Http\NotFoundHttpException;
use GlpiPlugin\Yagp\Config;
use GlpiPlugin\Yagp\Transfer;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!Plugin::isPluginActive('yagp')) {
    throw new NotFoundHttpException();
}

Session::checkRight('transfer', READ);

/** @var array $CFG_GLPI */
global $CFG_GLPI;

$_REQUEST['_in_modal'] = 1;
Html::header('yagp');

$transfer = new Transfer();

// The confirmed transfer only reads POST data (core CSRF check applies); GET only displays.
$is_confirm = isset($_POST['confirm_transfer']);
$request    = $is_confirm ? $_POST : $_GET;

if (isset($request['itemtype']) && isset($request['items_id'])) {
    $itemtype = $request['itemtype'];
    $id = (int) $request['items_id'];

    // Quick transfer is only offered on tickets, do not instantiate arbitrary itemtypes.
    if ($itemtype !== Ticket::class) {
        throw new AccessDeniedHttpException();
    }

    $item = new $itemtype();
    // Refuse ids coming from a creation form (-1) or pointing to a missing ticket,
    // and make sure the current user is actually allowed to transfer this one.
    if ($id <= 0 || !$item->getFromDB($id) || !$item->can($id, UPDATE)) {
        throw new AccessDeniedHttpException();
    }

    $transferlist = [];
    $transferlist[$itemtype][$id] = $id;

    $config = Config::getInstance();
    if (
        isset($config->fields['autotransfer'])
        && $config->fields['autotransfer'] == 1
        && isset($item->fields['entities_id'])
        && $item->fields['entities_id'] != $config->fields['transfer_entity']
        // Like the core transfer: never move a ticket to an entity the user cannot access.
        && Session::haveAccessToEntity((int) $config->fields['transfer_entity'])
    ) {
        if (!$is_confirm) {
            // State change only through a POST (core CSRF check applies), never on a plain GET.
            echo "<form method='post' action='" . htmlescape($CFG_GLPI['root_doc'] . '/plugins/yagp/ajax/quicktransfer.php')
                . "' class='d-flex w-100 justify-content-center mt-4'>";
            echo Html::hidden('itemtype', ['value' => $itemtype]);
            echo Html::hidden('items_id', ['value' => $id]);
            echo "<button type='submit' name='confirm_transfer' value='1' class='btn btn-primary'>"
                . __s('Execute') . "</button>";
            Html::closeForm();
            return;
        }

        $glpitransfer = new \Transfer();
        $glpitransfer->moveItems(
            $transferlist,
            (int) $config->fields['transfer_entity'],
            Transfer::getCompleteTransferOptions(),
        );

        $msg = __("Ticket transferred to %s", 'yagp');
        $sprintf = sprintf(
            $msg,
            Dropdown::getDropdownName('glpi_entities', $config->fields['transfer_entity']),
        );

        echo "<div class='d-flex w-100 justify-content-center align-items-center'>";
        echo "<div class='alert alert-info mt-4'>";
        echo "<h3>" . htmlescape($sprintf) . "</h3>";
        echo "<span class='text-muted'>" . htmlescape(__('You can close this window', 'yagp')) . "</span>";
        echo "</div>";
        echo "</div>";
    } else {
        $transfer->showTransferList($transferlist);
    }
}
