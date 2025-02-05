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

class PluginYagpTransfer extends CommonDBTM
{
    public function showForm($ID, array $options = [])
    {
        /*
        $edit_form = true;
        if (strpos($_SERVER['HTTP_REFERER'], "transfer.form.php") === false) {
            $edit_form = false;
        }
        */
        $edit_form = false; // custom form

        $transfer = new Transfer();
        $transfer->initForm($ID, $options);

        $params = [];
        if (!Session::haveRightsOr("transfer", [CREATE, UPDATE, PURGE])) {
            $params['readonly'] = true;
        }

        if ($edit_form) {
            $transfer->showFormHeader($options);
        } else {
            echo "<form method='post' name=form action='" . $options['target'] . "'>";
            echo "<div class='center' id='tabsbody' >";
            echo "<table class='tab_cadre_fixe'>";

            echo "<tr><td class='tab_bg_2 top' colspan='4'>";
            echo "<div class='center'>";
            echo "<input type='hidden' name='transferlist' value='" . json_encode($options['transferlist']) . "'>";
            Entity::dropdown(['name' => 'to_entity']);
            echo "&nbsp;<input type='submit' name='transfer' value=\"" . __s('Execute') . "\"
                    class='btn btn-primary'></div>";
            echo "</td></tr>";
        }

        if ($edit_form) {
            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Name') . "</td><td>";
            echo Html::input('name', ['value' => $transfer->fields['name']]);
            echo "</td>";
            echo "<td rowspan='3' class='middle right'>" . __('Comments') . "</td>";
            echo "<td class='center middle' rowspan='3'>
                <textarea class='form-control' name='comment' >" . $transfer->fields["comment"] . "</textarea>";
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Last update') . "</td>";
            echo "<td>" . ($transfer->fields["date_mod"] ? Html::convDateTime($transfer->fields["date_mod"])
                                                : __('Never'));
            echo "</td></tr>";
        }

        if (isset($options['display']) && $options['display'] == true) {
            $keep  = [0 => _x('button', 'Delete permanently'),
                1 => __('Preserve')
            ];

            $clean = [0 => __('Preserve'),
                1 => _x('button', 'Put in trashbin'),
                2 => _x('button', 'Delete permanently')
            ];

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Historical') . "</td><td>";
            $params['value'] = $transfer->fields['keep_history'];
            Dropdown::showFromArray('keep_history', $keep, $params);
            echo "</td>";
            if (!$edit_form) {
                echo "<td colspan='2'>&nbsp;</td>";
            }
            echo "</tr>";

            // Clean glpi 10.0.10 doesn't have this field in Transfer table
            if (isset($transfer->fields['keep_location'])) {
                echo "<tr class='tab_bg_1'>";
                echo "<td>" . _n('Location', 'Locations', 1) . "</td><td>";
                $location_option  = [
                    0 => __("Empty the location"),
                    1 => __('Preserve')
                ];
                $params['value'] = $transfer->fields['keep_location'];
                Dropdown::showFromArray('keep_location', $location_option, $params);
                echo "</td>";
                if (!$edit_form) {
                    echo "<td colspan='2'>&nbsp;</td>";
                }
                echo "</tr>";
            }

            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='4' class='center b'>" . _n('Asset', 'Assets', Session::getPluralNumber()) . "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . _n('Network port', 'Network ports', Session::getPluralNumber()) . "</td><td>";
            $options = [0 => _x('button', 'Delete permanently'),
                1 => _x('button', 'Disconnect') ,
                2 => __('Keep')
            ];
            $params['value'] = $transfer->fields['keep_networklink'];
            Dropdown::showFromArray('keep_networklink', $options, $params);
            echo "</td>";
            echo "<td>" . _n('Ticket', 'Tickets', Session::getPluralNumber()) . "</td><td>";
            $options = [0 => _x('button', 'Delete permanently'),
                1 => _x('button', 'Disconnect') ,
                2 => __('Keep')
            ];
            $params['value'] = $transfer->fields['keep_ticket'];
            Dropdown::showFromArray('keep_ticket', $options, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Software of items') . "</td><td>";
            $params['value'] = $transfer->fields['keep_software'];
            Dropdown::showFromArray('keep_software', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If software are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_software'];
            Dropdown::showFromArray('clean_software', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . _n('Reservation', 'Reservations', Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_reservation'];
            Dropdown::showFromArray('keep_reservation', $keep, $params);
            echo "</td>";
            echo "<td>" . _n('Component', 'Components', Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_device'];
            Dropdown::showFromArray('keep_device', $keep, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Links between printers and cartridge types and cartridges');
            echo "</td><td>";
            $params['value'] = $transfer->fields['keep_cartridgeitem'];
            Dropdown::showFromArray('keep_cartridgeitem', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If the cartridge types are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_cartridgeitem'];
            Dropdown::showFromArray('clean_cartridgeitem', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Links between cartridge types and cartridges') . "</td><td>";
            $params['value'] = $transfer->fields['keep_cartridge'];
            Dropdown::showFromArray('keep_cartridge', $keep, $params);
            echo "</td>";
            echo "<td>" . __('Financial and administrative information') . "</td><td>";
            $params['value'] = $transfer->fields['keep_infocom'];
            Dropdown::showFromArray('keep_infocom', $keep, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Links between consumable types and consumables') . "</td><td>";
            $params['value'] = $transfer->fields['keep_consumable'];
            Dropdown::showFromArray('keep_consumable', $keep, $params);
            echo "</td>";
            echo "<td>" . __('Links between computers and volumes') . "</td><td>";
            $params['value'] = $transfer->fields['keep_disk'];
            Dropdown::showFromArray('keep_disk', $keep, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Lock fields updated during transfer') . "</td><td>";
            Dropdown::showYesNo('lock_updated_fields', $transfer->fields['lock_updated_fields']);
            echo "</td>";
            echo "<td></td></tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='4' class='center b'>" . __('Direct connections') . "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . _n('Monitor', 'Monitors', Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_dc_monitor'];
            Dropdown::showFromArray('keep_dc_monitor', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If monitors are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_dc_monitor'];
            Dropdown::showFromArray('clean_dc_monitor', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . _n('Printer', 'Printers', Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_dc_printer'];
            Dropdown::showFromArray('keep_dc_printer', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If printers are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_dc_printer'];
            Dropdown::showFromArray('clean_dc_printer', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . Peripheral::getTypeName(Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_dc_peripheral'];
            Dropdown::showFromArray('keep_dc_peripheral', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If devices are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_dc_peripheral'];
            Dropdown::showFromArray('clean_dc_peripheral', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . _n('Phone', 'Phones', Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_dc_phone'];
            Dropdown::showFromArray('keep_dc_phone', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If phones are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_dc_phone'];
            Dropdown::showFromArray('clean_dc_phone', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_2'>";
            echo "<td colspan='4' class='center b'>" . __('Management') . "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . _n('Supplier', 'Suppliers', Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_supplier'];
            Dropdown::showFromArray('keep_supplier', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If suppliers are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_supplier'];
            Dropdown::showFromArray('clean_supplier', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . __('Links between suppliers and contacts') . "&nbsp;:</td><td>";
            $params['value'] = $transfer->fields['keep_contact'];
            Dropdown::showFromArray('keep_contact', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If contacts are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_contact'];
            Dropdown::showFromArray('clean_contact', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . Document::getTypeName(Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_document'];
            Dropdown::showFromArray('keep_document', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If documents are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_document'];
            Dropdown::showFromArray('clean_document', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . _n('Contract', 'Contracts', Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_contract'];
            Dropdown::showFromArray('keep_contract', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If contracts are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_contract'];
            Dropdown::showFromArray('clean_contract', $clean, $params);
            echo "</td></tr>";

            echo "<tr class='tab_bg_1'>";
            echo "<td>" . _n('Certificate', 'Certificates', Session::getPluralNumber()) . "</td><td>";
            $params['value'] = $transfer->fields['keep_certificate'];
            Dropdown::showFromArray('keep_certificate', $keep, $params);
            echo "</td>";
            echo "<td>" . __('If certificates are no longer used') . "</td><td>";
            $params['value'] = $transfer->fields['clean_certificate'];
            Dropdown::showFromArray('clean_certificate', $clean, $params);
            echo "</td></tr>";
        }
        if ($edit_form) {
            $transfer->showFormButtons($options);
        } else {
            echo "</table></div>";
            Html::closeForm();
        }
        return true;
    }

    /**
     * Display items to transfer
     * Original from GLPI, adapted to modal frames
     * @return void
     */
    public function showTransferList($transferlist = [])
    {
        global $DB, $CFG_GLPI;

        echo "<div class='d-flex w-100 flex-column'>";
        if (isset($transferlist) && count($transferlist)) {
            echo "<div class='my-2 text-center'>";
            echo "<span>" . __('Think of making a backup before transferring items.') . "</span>";
            echo "</div>";

            echo "<table class='mx-2 text-center' >";
            echo "<thead>";
            echo '<tr><th>' . __('Items to transfer') . '</th>';

            /*
            echo '<th>' . __('Transfer mode') . "&nbsp;";
            $rand = Transfer::dropdown([
                'name'      => 'id',
                'comments'  => false,
                'value'     => 1,
                'toupdate'  => [
                    'value_fieldname'   => 'id',
                    'to_update'         => "transfer_form",
                    //'url'               => $CFG_GLPI["root_doc"] . "/ajax/transfers.php"
                    'url'               => Plugin::getWebDir('yagp') . "/ajax/quicktransfer.php"
                ]
            ]);
            echo '</th></tr>';
            */
            echo "</thead>";

            echo "<tbody>";
            echo "<tr><td class=''>";
            /** @var class-string<CommonDBTM> $itemtype */
            foreach ($transferlist as $itemtype => $tab) {
                if (count($tab)) {
                    if (!($item = getItemForItemtype($itemtype))) {
                        continue;
                    }
                    $table = $itemtype::getTable();

                    $iterator = $DB->request([
                        'SELECT'    => [
                            "$table.id",
                            "$table.name",
                            'entities.completename AS locname',
                            'entities.id AS entID'
                        ],
                        'FROM'      => $table,
                        'LEFT JOIN' => [
                            'glpi_entities AS entities'   => [
                                'ON' => [
                                    'entities' => 'id',
                                    $table     => 'entities_id'
                                ]
                            ]
                        ],
                        'WHERE'     => ["$table.id" => $tab],
                        'ORDERBY'   => ['locname', "$table.name"]
                    ]);
                    $entID = -1;

                    if (count($iterator)) {
                        echo '<div class="d-flex justify-content-around py-2">';
                        foreach ($iterator as $data) {
                                echo '<span>' . $item->getTypeName() . '</span>';
                            if ($entID != $data['entID']) {
                                $entID = $data['entID'];
                                echo "<span>" . $data['locname'] . "</span>";
                            }
                                echo "<span>" . ($data['name'] ? $data['name'] : "(" . $data['id'] . ")") . "</span>";
                        }
                        echo '</div>';
                    }
                }
            }
            echo "</td></tr>";

            $display = false;
            echo "<tr><td class='' colspan='2'>";
            if (countElementsInTable('glpi_transfers') == 0) {
                echo __('No item found');
            } else {
                /*
                $params = ['id' => '__VALUE__'];
                Ajax::updateItemOnSelectEvent(
                    "dropdown_id$rand",
                    "transfer_form",
                    //$CFG_GLPI["root_doc"] . "/ajax/transfers.php",
                    Plugin::getWebDir('yagp') . "/ajax/quicktransfer.php",
                    $params
                );
                */
                //$display = true;
            }

            echo "<div class='center' id='transfer_form'><br>";
            $transfer = new self();
            $transfer->showForm(
                1,
                [
                    'target'        => Plugin::getWebDir('yagp') . "/front/transfer.form.php",
                    'display'       => $display,
                    'transferlist'  => $transferlist
                ]
            );
            /*
            Html::showSimpleForm(
                Plugin::getWebDir('yagp') . "/front/transfer.form.php",
                'clear',
                __('To empty the list of elements to be transferred')
            );
            */
            echo "</div>";
            echo '</td></tr>';

            echo "</tbody>";
            echo '</table>';
        } else {
            echo __('No selected element or badly defined operation');
        }
        echo "</div>";
    }

    /**
     * getCompleteTransferOptions
     *
     * @return array
     */
    public static function getCompleteTransferOptions(): array
    {
        $params = [
            'keep_ticket'           => 2,
            'keep_networklink'      => 2,
            'keep_reservation'      => 1,
            'keep_device'           => 1,
            'keep_history'          => 1,
            'keep_infocom'          => 1,
            'keep_dc_monitor'       => 1,
            'clean_dc_monitor'      => 1,
            'keep_dc_phone'         => 1,
            'clean_dc_phone'        => 1,
            'keep_dc_peripheral'    => 1,
            'clean_dc_peripheral'   => 1,
            'keep_dc_printer'       => 1,
            'clean_dc_printer'      => 1,
            'keep_contact'          => 1,
            'clean_contact'         => 1,
            'keep_contract'         => 1,
            'clean_contract'        => 1,
            'keep_software'         => 1,
            'clean_software'        => 1,
            'keep_document'         => 1,
            'clean_document'        => 1,
            'keep_cartidgeitem'     => 1,
            'clean_cartidgeitem'    => 1,
            'keep_cartidge'         => 1,
            'keep_consumable'       => 1,
            'keep_disk'             => 1,
            'keep_certificate'      => 1,
            'clean_certificate'     => 1,
            'lock_updated_fields'   => 0,
        ];

        return $params;
    }
}
