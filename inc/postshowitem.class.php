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
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
 }

 class PluginYagpPostshowitem extends CommonDBTM {

    public static function plugin_yagp_postShowItem($params) {
        Toolbox::logInFile("POSTSHOW",print_r($params,true));
        if (isset($params['item']) && $params['item'] instanceof CommonDBTM) {
            switch (get_class($params['item'])) {
               case 'Ticket':
                $script = <<<JAVASCRIPT
                $(document).ready(function() {
                    $("span.is-private[data-bs-original-title='Private']").children("i").css("font-size","1.6em");
                    $("span.is-private[data-bs-original-title='Private']").children("i").css("color","#d63939");
                    $("span.is-private[data-bs-original-title='Private']").children("i").css("font-weight","500");
                    $("span.is-private[data-bs-original-title='Private']").parent().parent().parent().css("border-style","dashed");
                    $("span.is-private[data-bs-original-title='Private']").parent().parent().parent().css("border-color","#d63939");
                    $("span.is-private[data-bs-original-title='Private']").parent().parent().parent().css("border-width","0.143em");
                    $("span.is-private[data-bs-original-title='Private']").parent().parent().parent().css("border-radius","3px");
                });
JAVASCRIPT;

                echo Html::scriptBlock($script);
            }
        }

    }
 }