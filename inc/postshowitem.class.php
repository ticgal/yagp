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
class PluginYagpPostshowitem extends CommonDBTM
{
    /**
     * @param  array $params
     *
     * @return bool
     */
    public static function pluginYagpPostShowItem(array $params): bool
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

                if ($config->fields['modal_satisfaction'] == 1) {
                    self::showSatisfactionModal($params);
                }
                break;
        }

        return true;
    }

    /**
     * enhancePrivateView
     *
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
                /** @var Ticket $item */
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
                            $config->fields['transfer_entity'],
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
                            'reloadonclose' => true,
                        ],
                    );
                    echo Html::scriptBlock($script);
                }
                break;
        }

        return true;
    }

    /**
     * @param array $params
     *
     * @return bool
     */
    public static function showSatisfactionModal(array $params): bool
    {
        $item = isset($params['item']) ? $params['item'] : null;
        if (!is_object($item)) {
            return false;
        }

        switch ($item->getType()) {
            case Ticket::class:
                /** @var Ticket $item */
                $ticket_status = $item->fields['status'];
                $ticket_entity = $item->fields['entities_id'];
                $ticket_satisfaction = new TicketSatisfaction();
                if (!$ticket_satisfaction->getFromDBByCrit(['tickets_id' => $item->fields['id']])) {
                    return false;
                }
                $duration = (int) self::getUsedConfig('inquest_config', $item->fields['entities_id'], 'inquest_duration', -2);
                $date2    = strtotime($ticket_satisfaction->fields['date_begin']);
                if ($ticket_status != Ticket::CLOSED) {
                    return false;
                } else {
                    $entity_config = self::getUsedConfig('inquest_config', $ticket_entity, 'inquest_delay', -2);
                    if ($entity_config != 0) {
                        return false;
                    } else {

                        if ($ticket_satisfaction->fields['satisfaction'] != null) {
                            return false;
                        } else {
                            if ($ticket_satisfaction->fields['satisfaction'] === null && $ticket_satisfaction->fields['date_answered'] != null) {
                                return false;
                            } else {
                                if (
                                    ($duration == 0)
                                    || (time() - $date2) <= $duration * DAY_TIMESTAMP
                                ) {
                                    // Meter codigo
                                    $ajax_id = 'ajax_satisfaction';
                                    $ajax_url = Plugin::getWebDir('yagp') . '/ajax/satisfaction.php?id=' . $item->fields['id'];
                                    $ajax_title = __('Satisfaction', 'yagp');

                                    Ajax::createIframeModalWindow(
                                        $ajax_id,
                                        $ajax_url,
                                        [
                                            'title'         => $ajax_title,
                                            'width'         => '500',
                                            'height'        => '500',
                                            'reloadonclose' => true,
                                        ],
                                    );

                                    echo "<script>
            $(document).ready(function() {
                var test_inteval = setInterval(function() {
                    if ($('#ajax_satisfaction').length > 0) {
                        $('#ajax_satisfaction').modal('show');
                        clearInterval(test_inteval);
                    }
                }, 100);
            });
        </script>";

                                } else {
                                    return false;
                                }
                            }
                            break;
                        }
                    }
                }
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public static function getUsedConfig($fieldref, $entities_id, $fieldval = '', $default_value = -2)
    {
        if (empty($fieldval)) {
            $fieldval = $fieldref;
        }

        $entity = new Entity();
        //$entity = new self();
        // Search in entity data of the current entity
        if ($entity->getFromDBByCrit(['id' => $entities_id])) {
            // Value is defined : use it
            if (isset($entity->fields[$fieldref])) {
                // Numerical value
                if (is_numeric($default_value) && ($entity->fields[$fieldref] != Entity::CONFIG_PARENT) && (!empty($entity->fields[$fieldref]))) {
                    return $entity->fields[$fieldval];
                }
                // String value
                if (!is_numeric($default_value) && $entity->fields[$fieldref]) {
                    return $entity->fields[$fieldval];
                }
            }
        }

        // Entity data not found or not defined : search in parent one
        if ($entities_id > 0) {
            $entity = new Entity();
            if ($entity->getFromDB($entities_id)) {
                $ret = self::getUsedConfig($fieldref, $entity->fields['entities_id'], $fieldval, $default_value);
                return $ret;
            }
        }

        return $default_value;
    }

    /**
     * @param int $ID
     *
     * @return void
     */
    public function showSatisfaction(int $ID): void
    {
        $satisfaction = new TicketSatisfaction();

        $satisfaction->getFromDBByCrit(['tickets_id' => $ID]);
        $ticket = new Ticket();
        $ticket->getFromDB($ID);
        $add = true;
        if ($satisfaction->getField('satisfaction') == null) {
            $add = false;
        }
        $rand = mt_rand();
        //$out = "<link rel='stylesheet' type='text/css' href='public/lib/jquery.rateit.css'>";
        $out = "<form name='costentity_form$rand' id='costentity_form$rand' method='post' action='";
        $out .= self::getFormUrl() . "'>";
        $out .= "<table class='tab_cadre_fixe'>";

        $out .= "<tr><td colspan='2'>";
        $out .= "<input type='hidden' name='id' value='$ID'>";
        $out .= "</td></tr>\n";
        $out .= "<tr class='tab_bg_2'>";
        $out .= "<td>";
        $out .= "<span>" . __('Satisfaction with the resolution of the ticket') . "</span> <br><br>";
        $out .= "<input type='hidden' name='tickets_id' value='$ID'>";
        $out .= "<select id='satisfaction_data' name='satisfaction'>";
        for ($i = 1; $i <= 5; $i++) {
            $out .= "<option value='$i' " . (($i == $satisfaction->getField('satisfaction')) ? 'selected' : '') .
                ">$i</option>";
        }
        $out .= "</select>";
        $out .= "<div class='rateit' id='stars'></div>";
        $out .=  "<script type='text/javascript'>";
        $out .=  "$(document).ready(function() {";
        //$out .= "$(function() {";
        $out .= "$('#stars').rateit({value: " . (int) $satisfaction->getField('satisfaction') . ",
                                    min : 1,
                                    max : 5,
                                    step: 1,
                                    backingfld: '#satisfaction_data',
                                    ispreset: true,
                                    resetable: false});";
        $out .= "});</script>";

        $out .= "</td></tr>";

        $out .= "<tr class='tab_bg_2'>";
        $out .= "<td rowspan='1' class='middle'>";
        $out .= "<span>" . __('Comentarios') . "</span><br><br>";
        $out .= "<textarea class='form-control' rows='10' cols='100' name='comment'>" . $satisfaction->getField('comment') . "</textarea>";
        $out .= "</td></tr>";
        $out .= "</tbody>";
        $out .= "</table>";
        if ($ticket->fields['status'] == Ticket::CLOSED) {
            if ($add == true) {
                $out .= "<input type='submit' name='add' value='" . _sx('button', 'Add') . "' class='submit'>";
            } else {
                $out .= "<input type='submit' name='update' value='" . _sx('button', 'Update') . "' class='submit'>";
            }
        }
        $out .= Html::closeForm(false);
        echo $out;
    }
}
