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
use GlpiPlugin\Yagp\Transfer;

if (!Plugin::isPluginActive('yagp')) {
    throw new NotFoundHttpException();
}

Session::checkRight("transfer", READ);

$items = [];
$to_entity = -1;
if (isset($_POST['transfer'], $_POST['transferlist'])) {
    $to_entity = (int) ($_POST['to_entity'] ?? -1);
    if ($to_entity < 0 || !Session::haveAccessToEntity($to_entity)) {
        throw new AccessDeniedHttpException();
    }
    $items = Transfer::validateTransferList(json_decode((string) $_POST['transferlist'], true));
}

$_REQUEST['_in_modal'] = 1;
Html::header('yagp');

if ($items !== []) {
    $options = $_POST;
    foreach (Transfer::getCompleteTransferOptions() as $k => $v) {
        $options[$k] ??= $v;
    }

    $transfer = new \Transfer();
    $transfer->moveItems($items, $to_entity, $options);

    $msg = __("Ticket transferred to %s", 'yagp');
    $sprintf = sprintf(
        $msg,
        Dropdown::getDropdownName('glpi_entities', $to_entity),
    );

    echo "<div class='d-flex w-100 justify-content-center align-items-center'>";
    echo "<div class='alert alert-info mt-4'>";
    echo "<h3>" . htmlescape($sprintf) . "</h3>";
    echo "<span class='text-muted'>" . htmlescape(__('You can close this window', 'yagp')) . "</span>";
    echo "</div>";
    echo "</div>";
}

Html::footer();
