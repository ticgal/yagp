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
class PluginYagpTicketsolveddate extends CommonDBTM
{
    /**
     * {@inheritdoc}
     */
    public static function getTypeName($nb = 0): string
    {
        return "YagpTicketSolvedDate";
    }

    /**
     * cronInfo
     *
     * @param  string $name
     * @return array
     */
    public static function cronInfo(string $name): array
    {
        switch ($name) {
            case 'changeDate':
                return [
                    'description'   => __('Change date', 'yagp'),
                    'parameter'     => __('Number of tickets', 'yagp'),
                ];
        }

        return [];
    }

    /**
     * @param CronTask $task
     *
     * @return int
     */
    public static function cronChangeDate(CronTask $task): int
    {
        /** @var \DBmysql $DB */
        global $DB;

        $config = PluginYagpConfig::getInstance();
        $tot = 0;
        if ($config->fields['ticketsolveddate']) {
            $message = "";
            $ticket = new Ticket();
            if ($task->fields['param'] > 0) {
                $limit = " LIMIT " . $task->fields['param'];
            } else {
                $limit = "";
            }

            // TODO: transform into array for iterator
            $query = "SELECT id,date,solvedate,taskstart.first_task_begin,task.last_task_end 
                FROM glpi_tickets AS ticket
                INNER JOIN (
                    SELECT tickets_id,CASE 
                        WHEN max(end)>max(ADDDATE(date,INTERVAL actiontime SECOND)) THEN max(end)
                        ELSE max(ADDDATE(date,INTERVAL actiontime SECOND))
                        END AS last_task_end
                    FROM glpi_tickettasks
                    GROUP BY tickets_id) AS task
                ON ticket.id=task.tickets_id
                LEFT JOIN (
                    SELECT tickets_id,min(begin)AS first_task_begin
                    FROM glpi_tickettasks
                    WHERE begin IS NOT NULL
                    GROUP BY tickets_id) AS taskstart
                ON ticket.id=taskstart.tickets_id
                WHERE ticket.status=5
                AND DATE_FORMAT(ticket.solvedate, '%Y-%m-%d %H:%i')<>DATE_FORMAT(task.last_task_end, '%Y-%m-%d %H:%i')
                AND ticket.is_deleted=0
            AND ticket.date<task.last_task_end {$limit}";

            foreach ($DB->doQuery($query) as $id => $row) {
                if (!is_null($row["first_task_begin"])) {
                    if ($row["date"] > $row["first_task_begin"]) {
                        $newdate = strtotime('-1 hour', strtotime($row["first_task_begin"]));
                        $newdate = date('Y-m-d H:i', $newdate);
                        $ticket->update(['id' => $row["id"], 'date' => $newdate]);
                        $task->addVolume(1);
                        $task->log("Updated Ticket open date id: " . $row["id"]);
                    }
                }
                $ticket->update(['id' => $row["id"], 'solvedate' => $row["last_task_end"]]);
                $task->addVolume(1);
                $tot++;
                $href = Ticket::getFormURLWithID($row["id"]);
                $task->log("<a href='" . $href . "'>Updated Ticket id: " . $row["id"] . "</a>");
            }
        }

        return ($tot > 0 ? 1 : 0);
    }
}
