<?php

/**
 * -------------------------------------------------------------------------
 * YAGP plugin for GLPI
 * Copyright (C) 2019-2026 by the TICgal Team.
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
 * @copyright Copyright (c) 2026 TICGAL team
 * @license   AGPL License 3.0 or (at your option) any later version
 *            http://www.gnu.org/licenses/agpl-3.0-standalone.html
 * @link      https://www.tic.gal
 * @since     2019
 * -------------------------------------------------------------------------
 */

namespace GlpiPlugin\Yagp;

use CommonDBTM;
use CronTask;
use Glpi\DBAL\QueryExpression;
use Glpi\DBAL\QuerySubQuery;

class Ticketsolveddate extends CommonDBTM
{
    /**
     * {@inheritdoc}
     */
    public static function getTypeName($nb = 0): string
    {
        return "YagpTicketSolvedDate";
    }

    /**
     * Register the "changeDate" automatic action (enabled from the plugin configuration)
     *
     * @return void
     */
    public static function registerCronTask(): void
    {
        CronTask::register(self::class, 'changeDate', HOUR_TIMESTAMP, [
            'state' => CronTask::STATE_WAITING,
            'mode'  => CronTask::MODE_EXTERNAL,
        ]);
    }

    /**
     * Remove the "changeDate" automatic action, and only this one
     *
     * @return void
     */
    public static function unregisterCronTask(): void
    {
        $crontask = new CronTask();
        if ($crontask->getFromDBbyName(self::class, 'changeDate')) {
            $crontask->delete(['id' => $crontask->getID()]);
        }
    }

    /**
     * @param \Migration $migration
     *
     * @return void
     */
    public static function install(\Migration $migration): void
    {
        /** @var \DBmysql $DB */
        global $DB;

        // 4.0.0: classes moved to the GlpiPlugin\Yagp namespace
        $DB->update(
            CronTask::getTable(),
            ['itemtype' => self::class],
            ['itemtype' => 'PluginYagpTicketsolveddate'],
        );
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

        $config = Config::getInstance();
        $tot = 0;
        if ($config->fields['ticketsolveddate']) {
            $message = "";
            $ticket = new \Ticket();
            $task_end    = 'MAX(' . $DB->quoteName('end') . ')';
            $action_end  = 'MAX(ADDDATE(' . $DB->quoteName('date') . ', INTERVAL ' . $DB->quoteName('actiontime') . ' SECOND))';

            // Last task end per ticket: latest of the planned end and date + duration
            $last_task = new QuerySubQuery([
                'SELECT'  => [
                    'tickets_id',
                    new QueryExpression(
                        "CASE WHEN $task_end > $action_end THEN $task_end ELSE $action_end END",
                        'last_task_end',
                    ),
                ],
                'FROM'    => \TicketTask::getTable(),
                'GROUPBY' => 'tickets_id',
            ], 'task');

            $first_task = new QuerySubQuery([
                'SELECT'  => [
                    'tickets_id',
                    new QueryExpression('MIN(' . $DB->quoteName('begin') . ')', 'first_task_begin'),
                ],
                'FROM'    => \TicketTask::getTable(),
                'WHERE'   => ['NOT' => ['begin' => null]],
                'GROUPBY' => 'tickets_id',
            ], 'taskstart');

            $minute_format = $DB->quoteValue('%Y-%m-%d %H:%i');
            $criteria = [
                'SELECT'     => [
                    'ticket.id',
                    'ticket.date',
                    'ticket.solvedate',
                    'taskstart.first_task_begin',
                    'task.last_task_end',
                ],
                'FROM'       => \Ticket::getTable() . ' AS ticket',
                'INNER JOIN' => [
                    [
                        'TABLE' => $last_task,
                        'FKEY'  => ['ticket' => 'id', 'task' => 'tickets_id'],
                    ],
                ],
                'LEFT JOIN'  => [
                    [
                        'TABLE' => $first_task,
                        'FKEY'  => ['ticket' => 'id', 'taskstart' => 'tickets_id'],
                    ],
                ],
                'WHERE'      => [
                    'ticket.status'     => \CommonITILObject::SOLVED,
                    'ticket.is_deleted' => 0,
                    new QueryExpression(
                        'DATE_FORMAT(' . $DB->quoteName('ticket.solvedate') . ", $minute_format)"
                        . ' <> DATE_FORMAT(' . $DB->quoteName('task.last_task_end') . ", $minute_format)",
                    ),
                    new QueryExpression(
                        $DB->quoteName('ticket.date') . ' < ' . $DB->quoteName('task.last_task_end'),
                    ),
                ],
            ];
            if ($task->fields['param'] > 0) {
                $criteria['LIMIT'] = (int) $task->fields['param'];
            }

            foreach ($DB->request($criteria) as $row) {
                if (!is_null($row["first_task_begin"])) {
                    if ($row["date"] > $row["first_task_begin"]) {
                        $newdate = strtotime('-1 hour', strtotime($row["first_task_begin"]));
                        $newdate = date('Y-m-d H:i:s', $newdate);
                        // Bypasses "block opening date": this action is meant to adjust it
                        Ticket::updateOpeningDate($ticket, (int) $row["id"], $newdate);
                        $task->addVolume(1);
                        $task->log("Updated Ticket open date id: " . $row["id"]);
                    }
                }
                $ticket->update(['id' => $row["id"], 'solvedate' => $row["last_task_end"]]);
                $task->addVolume(1);
                $tot++;
                $href = \Ticket::getFormURLWithID($row["id"]);
                $task->log("<a href='" . $href . "'>Updated Ticket id: " . $row["id"] . "</a>");
            }
        }

        return ($tot > 0 ? 1 : 0);
    }
}
