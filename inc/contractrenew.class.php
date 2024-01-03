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

/**
 * Deprecated
 * GLPI 10.0.x renew contracts successfully
 */
class PluginYagpContractrenew extends CommonDBTM
{
    public static function getTypeName($nb = 0)
    {
        return "YagpContractRenew";
    }

    public static function cronInfo($name)
    {
        switch ($name) {
            case 'renewContract':
                return ['description' => __('Renews tacit contracts', 'yagp')];
        }
        return [];
    }

    public static function cronRenewContract($task)
    {
        global $DB, $CFG_GLPI;

        $query = [
            'FROM' => 'glpi_contracts',
            'WHERE' => [
                'renewal' => 1,
                [
                    'NOT' => ['begin_date' => null],
                ],
                'RAW' => [
                    'DATEDIFF(
                        ADDDATE(
                            ' . DBmysql::quoteName('begin_date') . ',
                            INTERVAL ' . DBmysql::quoteName('duration') . ' MONTH
                        ),
                        CURDATE()
                    )' => ['<=', 1]
                ]
            ]
        ];
        $contract = new Contract();
        foreach ($DB->request($query) as $id => $row) {
            $contract->update([
                'id'        => $row["id"],
                'duration'  => ($row['duration'] + $row['periodicity'])
            ]);
            $task->addVolume(1);
            $task->log("<a href='" . Contract::getFormURLWithID($row["id"]) . "'>" . sprintf(__("Renewed Contract id: %s", "yagp"), $row["id"]) . "</a>");
        }

        return true;
    }
}
