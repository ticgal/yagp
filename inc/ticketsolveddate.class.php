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

class PluginYagpTicketsolveddate extends CommonDBTM
{
    /**
     * getTypeName
     *
     * @param  mixed $nb
     * @return string
     */
    public static function getTypeName($nb = 0): string
    {
        return "YagpTicketSolvedDate";
    }

    /**
     * cronInfo
     *
     * @param  mixed $name
     * @return array
     */
    public static function cronInfo($name): array
    {
        switch ($name) {
            case 'changeDate':
                return [
                    'description'   => __('Change date', 'yagp'),
                    'parameter'     => __('Number of tickets', 'yagp')
                ];
        }
        return [];
    }

    public static function cronChangeDate($task)
    {
        global $DB;

        $config = PluginYagpConfig::getInstance();
        if ($config->fields['ticketsolveddate']) {
            $message = "";
            $ticket = new Ticket();
            if ($task->fields['param'] > 0) {
                $limit = " LIMIT " . $task->fields['param'];
            } else {
                $limit = "";
            }

           /*$sub_task=new QuerySubQuery([
            'SELECT'=>[
               'tickets_id',
               'MAX'=>'end AS last_task_end',
            ],
            'FROM'=>'glpi_tickettasks',
            'WHERE'=>[
               [
                  'NOT' => ['end' => null],
               ],
            ],
            'GROUPBY'=>'tickets_id',
            'AS task'
           ]);
           $sub_taskstart=new QuerySubQuery([
            'SELECT'=>[
               'tickets_id',
               'MIN'=>'begin AS first_task_begin',
            ],
            'FROM'=>'glpi_tickettasks',
            'WHERE'=>[
               [
                  'NOT' => ['begin' => null],
               ],
            ],
            'GROUPBY'=>'tickets_id',
            'AS taskstart'
           ]);
           $query=[
            'SELECT'=>[
               'id',
               'date',
               'solvedate',
            ],
            'FROM'=>'glpi_tickets',
            'INNER JOIN'=>[
               $sub_task=>[
                  'FKEY'=>[
                     'glpi_tickets'=>'id',
                     'task'=>'tickets_id',
                  ]
               ],
               $sub_taskstart=>[
                  'FKEY'=>[
                     'glpi_tickets'=>'id',
                     'taskstart'=>'tickets_id',
                  ]
               ]
            ],
            'WHERE'=>[
               'status'=>['>=','5'],
               'is_deleted'=>0,
               'solvedate'=>['!=','last_task_end']
            ]
           ];*/
            $query = "SELECT id,date,solvedate,taskstart.first_task_begin,task.last_task_end 
             FROM glpi_tickets as ticket
             INNER JOIN (
                 select tickets_id,CASE 
                     WHEN max(end)>max(ADDDATE(date,INTERVAL actiontime SECOND)) THEN max(end)
                     ELSE max(ADDDATE(date,INTERVAL actiontime SECOND))
                     END as last_task_end
                 from glpi_tickettasks
                 group by tickets_id) as task
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
         AND ticket.date<task.last_task_end" . $limit;

            foreach ($DB->request($query) as $id => $row) {
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
                $task->log("<a href='" . Ticket::getFormURLWithID($row["id"]) . "'>Updated Ticket id: " . $row["id"] . "</a>");
            }
            return true;
        } else {
            return false;
        }
    }
}
