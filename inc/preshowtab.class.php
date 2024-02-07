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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginYagpPreshowtab extends CommonDBTM
{
    public static function preShowTab($params = [])
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
                        $(".tab_cadre_fixe tbody:first").append("<tr><th colspan='2'>{$string}</th><th colspan='2'>{$validation_percent}%</th></tr>");
                    });
JAVASCRIPT;
                echo Html::scriptBlock($script);
        }
    }

    public static function plugin_yagp_preShowTab($params)
    {
        if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
            $options = $params["options"];
            switch ($options["itemtype"]) {
                case "Log":
                    $script = <<<JAVASCRIPT
                    $(document).ready(function() {
                        console.log($("a[data-bs-target^='#tab-Log']").get());
                        $("div[id^='tab-Log']").css({display:"none"});
                        $("div[id^='tab--'] div.table-responsive").css({display:"none"});
                    });
JAVASCRIPT;

                    echo Html::scriptBlock($script);
            }
        }
    }
}
