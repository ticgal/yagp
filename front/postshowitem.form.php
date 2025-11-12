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

include('../../../inc/includes.php');

if (!Plugin::isPluginActive('yagp')) {
    Html::displayNotFoundError();
}

Session::checkRight("transfer", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$_REQUEST['_in_modal'] = 1;
Html::header('yagp');

if (isset($_POST['add'])) {
    $ticket_satisfaction = new TicketSatisfaction();
    $ticket_satisfaction->add($_POST);
    $sprintf = "Satisfaction updated";
    echo "<div class='d-flex w-100 justify-content-center align-items-center'>";
    echo "<div class='alert alert-info mt-4'>";
    echo "<h3>" . $sprintf . "</h3>";
    echo "<span class='text-muted'>" . __('You can close this window', 'yagp') . "</span>";
    echo "</div>";
    echo "</div>";
}
if (isset($_POST['update'])) {
    $ticket_satisfaction = new TicketSatisfaction();
    $ticket_satisfaction->update($_POST);
    $sprintf = "Satisfaction updated";
    echo "<div class='d-flex w-100 justify-content-center align-items-center'>";
    echo "<div class='alert alert-info mt-4'>";
    echo "<h3>" . $sprintf . "</h3>";
    echo "<span class='text-muted'>" . __('You can close this window', 'yagp') . "</span>";
    echo "</div>";
    echo "</div>";
}
if (empty($_POST)) {
    $sprintf = "Satisfaction updated";
    echo "<div class='d-flex w-100 justify-content-center align-items-center'>";
    echo "<div class='alert alert-info mt-4'>";
    echo "<h3>" . $sprintf . "</h3>";
    echo "<span class='text-muted'>" . __('You can close this window', 'yagp') . "</span>";
    echo "</div>";
    echo "</div>";
}
