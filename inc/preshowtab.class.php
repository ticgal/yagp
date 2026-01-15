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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class PluginYagpPreshowtab extends CommonDBTM
{
    /**
     * @param  array $params
     *
     * @return void
     */
    /*
    public static function preShowTab(array $params = []): void
    {
        $config = PluginYagpConfig::getInstance();
        $options = $params["options"];
        switch ($options["itemtype"]) {
            case "TicketValidation":
                $ticket = new Ticket();
                $ticket->getFromDB($options["id"]);
                $validation_percent = $ticket->fields["validation_percent"];
                $df_min_validation = $config->fields["df_min_validation"];
                $string = __("Current minimum validation", "yagp");

                $script = <<<JAVASCRIPT
$(document).ready(function() {
    $("select[name='validation_percent'] option").attr("value",'{$df_min_validation}');
    $("select[name='validation_percent'] option").text('{$df_min_validation}%');
    $(".tab_cadre_fixe tbody:first").append(
        "<tr><th colspan='2'>{$string}</th><th colspan='2'>{$validation_percent}%</th></tr>"
    );
});
JAVASCRIPT;
                echo Html::scriptBlock($script);
                break;
        }
    }
*/
    /**
     * @param  array $params
     *
     * @return void
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public static function plugin_yagp_preShowTab(array $params): void
    {
        if (
            isset($_SESSION["glpiactiveprofile"])
            && isset($_SESSION["glpiactiveprofile"]["interface"])
            && $_SESSION["glpiactiveprofile"]["interface"] == "helpdesk"
        ) {
            $options = $params["options"];
            switch ($options["itemtype"]) {
                case "Log":
                    $script = <<<JAVASCRIPT
$(document).ready(function() {
    $("div[id^='tab-Log']").css({display:"none"});
    $("div[id^='tab--'] div.table-responsive").css({display:"none"});
});
JAVASCRIPT;

                    echo Html::scriptBlock($script);
                    break;
            }
        }
    }
}
