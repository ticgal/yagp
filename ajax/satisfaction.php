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
use GlpiPlugin\Yagp\Postshowitem;

header("Content-Type: text/html; charset=UTF-8");
Html::header_nocache();

if (!Plugin::isPluginActive('yagp')) {
    throw new NotFoundHttpException();
}

Session::checkLoginUser();

$id = (int) ($_GET['id'] ?? 0);
$ticket = new Ticket();
if ($id <= 0 || !$ticket->getFromDB($id)) {
    throw new NotFoundHttpException();
}
if (!$ticket->canViewItem()) {
    throw new AccessDeniedHttpException();
}
$ts = new TicketSatisfaction();
if (!$ts->getFromDBByCrit(['tickets_id' => $id])) {
    throw new NotFoundHttpException();
}

// Content is displayed in a modal
$_REQUEST['_in_modal'] = 1;
Html::popHeader(Postshowitem::getTypeName(1));
$ts->fields['name'] = $ticket->fields['name'];
$ts->showSatisfactionForm($ticket);
Html::popFooter();
