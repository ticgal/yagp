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

class PluginYagpPostshowitem extends CommonDBTM
{
    /**
     * pluginYagpPostShowItem
     *
     * @param  mixed $params
     * @return bool
     */
    public static function pluginYagpPostShowItem($params): bool
    {
        $item = isset($params['item']) ? $params['item'] : null;
        if (!is_object($item)) {
            return false;
        }

        $config = PluginYagpConfig::getInstance();
        switch (get_class($params['item'])) {
            case 'Ticket':
                if ($config->fields['private_view'] == 1) {
                    self::enhancePrivateView();
                }

                if ($config->fields['quick_transfer'] == 1) {
                    self::quickTransfer($params);
                }
                break;
        }

        return true;
    }

    /**
     * enhancePrivateView
     *
     * @param  mixed
     * @return void
     */
    public static function enhancePrivateView(): void
    {
        $script = <<<JAVASCRIPT
        $(document).ready(function() {
            $("span.is-private").children("i").css({
                "font-size":"1.6em",
                "color":"#d63939",
                "font-weight":"500"
            });
            $("span.is-private").parent().parent().parent().css({
                "border-style":"dashed",
                "border-color":"black",
                "border-width":"0.143em",
                "border-radius":"3px"
            });
        });
JAVASCRIPT;

        echo Html::scriptBlock($script);
    }

    /**
     * quickTransfer
     *
     * @param  array $params
     * @return bool
     */
    public static function quickTransfer(array $params): bool
    {
        $item = isset($params['item']) ? $params['item'] : null;
        if (!is_object($item)) {
            return false;
        }

        switch ($item->getType()) {
            case Ticket::class:
                $config = PluginYagpConfig::getInstance();
                if (
                    Session::haveRight('transfer', READ)
                    && Session::isMultiEntitiesMode()
                    && isset($item->fields['entities_id'])
                ) {
                    $entity_name = __("Select an entity to transfer", "yagp");
                    $ajax_id = 'ajax_playground';
                    $ajax_url = Plugin::getWebDir('yagp') . '/ajax/quicktransfer.php';
                    $ajax_url .= "?itemtype={$item->getType()}&items_id={$item->getID()}";
                    $ajax_title = __("Transfer to", "yagp");
                    if (
                        $config->fields['autotransfer'] == 1
                        && $config->fields['transfer_entity'] != $item->fields['entities_id']
                    ) {
                        $entity_name = Dropdown::getDropdownName(
                            'glpi_entities',
                            $config->fields['transfer_entity']
                        );
                        $ajax_title .= " $entity_name";
                    }
                    $icon = "<i class='fa-fw fas fa-level-up-alt'></i>";
                    $btn_attrs = "class='btn col-auto col-xxl-12' data-bs-toggle='modal'";

                    $append = "<label class='col-form-label col-xxl-4 text-xxl-end'></label>";
                    $append .= "<div class='col-xxl-8 row m-0 field-container'>";
                    $append .= "<a {$btn_attrs} data-bs-target='#{$ajax_id}'";
                    $append .= "data-toggle='tooltip' title='{$entity_name}' href='#'>";
                    $append .= $icon . "<span class='text-truncate'>$ajax_title</span>";
                    $append .= "</a>";
                    $append .= "</div>";

                    $script = <<<JAVASCRIPT
                    $('div#item-main .form-field').first().append("{$append}");
JAVASCRIPT;

                    Ajax::createIframeModalWindow(
                        $ajax_id,
                        $ajax_url,
                        [
                            'title'         => $ajax_title,
                            'width'         => '500',
                            'height'        => '500',
                            'reloadonclose' => true
                        ]
                    );
                    echo Html::scriptBlock($script);
                }
                break;
        }

        return true;
    }
}
