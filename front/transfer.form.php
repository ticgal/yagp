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

include('../../../inc/includes.php');

if (!Plugin::isPluginActive('yagp')) {
    throw new NotFoundHttpException();
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
        if (!Session::haveAccessToEntity((int) $_POST['to_entity'])) {
            throw new AccessDeniedHttpException();
        }

        $items = json_decode($_POST['transferlist'], true);
        if (!is_array($items)) {
            throw new AccessDeniedHttpException();
        }
        PluginYagpTransfer::validateTransferList($items);

        // Rebuild the list from integer ids only, so moveItems() gets exactly what was checked.
        $ids   = array_map('intval', array_values($items[Ticket::class] ?? []));
        $items = [Ticket::class => array_combine($ids, $ids)];

        // Same bar as core: the user must be able to update every ticket to move.
        foreach ($ids as $items_id) {
            $ticket = new Ticket();
            if ($items_id <= 0 || !$ticket->can($items_id, UPDATE)) {
                throw new AccessDeniedHttpException();
            }
        }

        // Never forward request data as transfer options.
        $transfer->moveItems(
            $items,
            (int) $_POST['to_entity'],
            PluginYagpTransfer::getCompleteTransferOptions(),
        );

        $entity = new Entity();
        $entity->getFromDB((int) $_POST['to_entity']);

        $msg = __("Ticket transferred to %s", 'yagp');
        $sprintf = sprintf(
            $msg,
            htmlescape(Dropdown::getDropdownName('glpi_entities', (int) $_POST['to_entity'])),
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
