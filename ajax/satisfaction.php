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
 Html::popHeader(
    PluginYagpPostshowitem::getTypeName(1),
    $_SERVER['PHP_SELF'],
    false,
    '',
    '',
    PluginYagpPostshowitem::getType(),
 );
 
 // Indicar que el contenido se carga en un modal
 $_REQUEST['_in_modal'] = 1;
 $id = $_GET['id'];
$ticket = new Ticket();
$ticket->getFromDB($id);
$satisfaction = new PluginYagpPostshowitem();
$satisfaction->showSatisfaction($id);
 // Contenido del modal
 Html::popFooter();