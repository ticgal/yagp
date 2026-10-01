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

$_REQUEST['_in_modal'] = 1;
Html::header('yagp');

$transfer = new Transfer();
if (isset($_GET['itemtype']) && isset($_GET['items_id'])) {
    $itemtype = $_GET['itemtype'];
    $id = (int) $_GET['items_id'];

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
    ) {
        // The automatic transfer changes data on a GET request (the modal iframe URL):
        // refuse it when the browser says the request does not come from GLPI itself.
        if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin') !== 'same-origin') {
            throw new AccessDeniedHttpException();
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
