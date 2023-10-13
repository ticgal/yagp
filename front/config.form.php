<?php

/*
 -------------------------------------------------------------------------
 YAGP plugin for GLPI
 Copyright (C) 2019-2022 by the TICgal Team.
 https://tic.gal/en/project/yagp-yet-another-glpi-plugin/
 -------------------------------------------------------------------------
 LICENSE
 This file is part of the YAGP plugin.
 YAGP plugin is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.
 YAGP plugin is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with YAGP. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 @package   YAGP
 @author    the TICgal team
 @copyright Copyright (c) 2019-2022 TICgal team
 @license   AGPL License 3.0 or (at your option) any later version
            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 @link      https://tic.gal/en/project/yagp-yet-another-glpi-plugin/
 @since     2019-2022
 ----------------------------------------------------------------------
*/

include("../../../inc/includes.php");

$config = new PluginYagpConfig();
if (isset($_POST["update"])) {
    if (
        isset($_POST['autoclose_rejected_tickets'])
        && $_POST['autoclose_rejected_tickets'] == 1
    ) {
        $solutiontype = new SolutionType();
        $solutionname = "Auto-close rejected tickets";
        if (!$solutiontype->getFromDBByCrit(['name' => $solutionname])) {
            $newID = $solutiontype->add([
                'name'      => $solutionname,
                'comment'   => __('Solution type for rejected tickets'),
            ]);
            if ($newID) {
                $msg = __("A solution type has been created for rejected tickets", "yagp");
                Session::addMessageAfterRedirect($msg, false, INFO);
                $_POST['solutiontypes_id_rejected'] = $newID;
            }
        }
    }
    $config->check($_POST['id'], UPDATE);
    // save
    $config->update($_POST);
    Html::back();
} elseif (isset($_POST["refresh"])) {
    // Undefined function refresh ?
    $config->refresh($_POST); // used to refresh process list, task category list
    Html::back();
}

$redirect = $CFG_GLPI["root_doc"] . "/front/config.form.php";
$redirect .= "?forcetab=" . urlencode('PluginYagpConfig$1');
Html::redirect($redirect);
