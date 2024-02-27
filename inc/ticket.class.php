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

class PluginYagpTicket extends CommonDBTM
{
    public static $rightname = 'ticket';

    public static function getSpecificValueToDisplay($field, $values, array $options = [])
    {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'is_recategorized':
                $ticket = new Ticket();
                $ticket->getFromDB($options["raw_data"]["id"]);

                $ticket_cat = new self();
                $ticket_cat->getFromDBByCrit(["tickets_id" => $options["raw_data"]["id"]]);
                if (empty($ticket_cat->fields)) {
                    return __("No");
                } else {
                    return __("Yes");
                }
                break;
            case 'plugin_yagp_itilcategories_id':
                $ticket = new Ticket();
                $ticket->getFromDB($options["raw_data"]["id"]);
                $ticket_cat = new self();
                $ticket_cat->getFromDBByCrit(["tickets_id" => $options["raw_data"]["id"]]);
                if (!empty($ticket_cat->fields)) {
                    if ($ticket_cat->fields["plugin_yagp_itilcategories_id"] == 0) {
                        return " ";
                    } else {
                        $cat = new ITILCategory();
                        $cat->getFromDB($ticket_cat->fields["plugin_yagp_itilcategories_id"]);
                        if (!empty($ticket_cat->fields)) {
                            return $cat->fields["completename"];
                        }
                    }
                }
                break;
        }

        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    public static function getSpecificValueToSelect($field, $name = '', $values = '', array $options = [])
    {
        global $DB;

        if (!is_array($values)) {
            $values = [$field => $values];
        }

        switch ($field) {
            case 'is_recategorized':
                $values = [
                    0 => __("No"),
                    1 => __("Yes")
                ];

                return  Dropdown::showFromArray($name, $values, $options);
                break;
            case 'plugin_yagp_itilcategories_id':
                $query = [
                    "SELECT" => "plugin_yagp_itilcategories_id",
                    "DISTINCT" => true,
                    "FROM" => PluginYagpTicket::getTable(),
                    "GROUPBY" => 'plugin_yagp_itilcategories_id'
                ];

                $values = [];
                foreach ($DB->request($query) as $row) {
                    $cat = new ITILCategory();
                    $cat->getFromDB($row["plugin_yagp_itilcategories_id"]);
                    if ($row["plugin_yagp_itilcategories_id"] !== 0) {
                        $values[$row["plugin_yagp_itilcategories_id"]] = $cat->fields["completename"];
                    }
                }
                $values[0] = __("without");
                return Dropdown::showFromArray($name, $values, $options);
                break;
        }
        return parent::getSpecificValueToSelect($field, $name, $values, $options);
    }

    /**
     * postItemForm
     *
     * @param  mixed $params
     * @return void
     */
    public static function postItemForm($params = []): void
    {
        var_dump($params);
        $item = $params['item'];
        if (!is_array($item) && $item->getType() == Ticket::getType()) {
            $date = ($item->getID()) ? $item->fields['date'] : '';
            $script = <<<JAVASCRIPT
$(document).ready(function() {
    console.log($("input[name='date']").parent());
    $("input[name='date']").parent().parent().html("{$date}");
});
JAVASCRIPT;
            if ($date != null) {
                echo Html::scriptBlock($script);
            }
        }
    }

    /**
     * preAddTicket
     *
     * @param  mixed $ticket
     * @return Ticket
     */
    public static function preAddTicket(Ticket $ticket): Ticket
    {
        $config = PluginYagpConfig::getInstance();
        $pattern = "/" . $config->fields['requestlabel'] . ".*" . $config->fields['requestlabel'] . "/i";

        if (isset($ticket->input['_message'])) {
            $mail = $ticket->input['_message'];
            $content = $mail->getContent();
            Toolbox::logInFile('yagp_requester', 'ORIGINAL: ' . print_r($content, true) . PHP_EOL);
            Toolbox::logInFile('yagp_requester', 'ENCODING: ' . print_r(mb_detect_encoding($content), true) . PHP_EOL);
            if (mb_detect_encoding($content) == 'ASCII') {
                $content = quoted_printable_decode($content);
            }
            // check if $content is a string in base64
            if (base64_encode(base64_decode($content, true)) === $content) {
                $content = base64_decode($content);
            }
            Toolbox::logInFile('yagp_requester', 'DECODED: ' . print_r($content, true) . PHP_EOL);

            if (preg_match_all($pattern, $content, $matches)) {
                Toolbox::logInFile('yagp_requester', 'MATCHES: ' . print_r($matches, true) . PHP_EOL);
                $string = $matches[0];
                $useremail = str_replace($config->fields['requestlabel'], "", $string);
                $useremail = str_replace(" ", "", $useremail); // remove possible spaces
                Toolbox::logInFile('yagp_requester', 'EMAIL: ' . print_r($useremail, true) . PHP_EOL);
                $user = new User();
                if (isset($useremail[0]) && filter_var($useremail[0], FILTER_VALIDATE_EMAIL)) {
                    Toolbox::logInFile('yagp_requester', 'EMAIL VALIDATED' . PHP_EOL);
                    if ($user->getFromDBbyEmail($useremail[0])) {
                        Toolbox::logInFile('yagp_requester', 'USER EXISTS' . PHP_EOL);
                        $ticket->input['_users_id_requester'] = $user->fields['id'];
                    } elseif ($config->fields['allow_anonymous_requester']) {
                        Toolbox::logInFile('yagp_requester', 'USER ANONYMOUS' . PHP_EOL);
                        $ticket->input['_users_id_requester'] = 0;
                        $ticket->input['_actors']['requester'][0]['itemtype']          = User::class;
                        $ticket->input['_actors']['requester'][0]['items_id']          = 0;
                        $ticket->input['_actors']['requester'][0]['use_notification']  = 1;
                        $ticket->input['_actors']['requester'][0]['alternative_email'] = $useremail[0];
                    } else {
                        Toolbox::logInFile('yagp_requester', 'NO CHANGES' . PHP_EOL);
                        return $ticket;
                    }

                    $mailgate = new MailCollector();
                    $mailgate->getFromDB($ticket->input['_mailgate']);
                    $rule_options['ticket']              = $ticket->input;
                    $rule_options['headers']             = $mailgate->getHeaders($ticket->input['_message']);
                    $rule_options['mailcollector']       = $ticket->input['_mailgate'];
                    $rule_options['_users_id_requester'] = $ticket->input['_users_id_requester'];
                    $rulecollection                      = new RuleMailCollectorCollection();
                    $output                              = $rulecollection->processAllRules(
                        [],
                        [],
                        $rule_options
                    );
                    foreach ($output as $key => $value) {
                        $ticket->input[$key] = $value;
                    }
                }
            }
        }

        return $ticket;
    }

    /**
     * itemUpdate
     *
     * @param  mixed $ticket
     * @return void
     */
    public static function pluginYagpItemUpdate($item): void
    {
        $config = PluginYagpConfig::getInstance();

        switch ($item::class) {
            case Ticket::class:
                if ($config->fields['recategorization']) {
                    self::ticketRecategorization($item);
                }
                break;
        }
    }

    /**
     * itemAdd
     *
     * @param  mixed $ticket
     * @return void
     */
    public static function pluginYagpItemAdd($item): void
    {
        $config = PluginYagpConfig::getInstance();

        switch ($item::class) {
            case ITILFollowup::class:
                if ($config->fields['autoclose_rejected_tickets']) {
                    self::autocloseRejectedTickets($item);
                }
                break;
            case ITILSolution::class:
                $solutiontypes = importArrayFromDB($config->fields['solutiontypes']);
                if (in_array($item->fields['solutiontypes_id'], $solutiontypes)) {
                    self::autocloseTicket($item);
                }
                break;
        }
    }

    /**
     * ticketRecategorization
     *
     * @param  mixed $ticket
     * @return void
     */
    public static function ticketRecategorization($ticket): void
    {
        if (isset($ticket->oldvalues["itilcategories_id"])) {
            $ticket_cat = new self();
            $ticket_cat->getFromDBByCrit(["tickets_id" => $ticket->fields["id"]]);
            if (empty($ticket_cat->fields)) {
                $ticket_cat->add([
                    "tickets_id"                    => $ticket->fields["id"],
                    "plugin_yagp_itilcategories_id" => $ticket->oldvalues["itilcategories_id"]
                ]);
            }
        }
    }

    /**
     * autocloseRejectedTickets
     *
     * @param  mixed $params
     * @return bool
     */
    public static function autocloseRejectedTickets($item): bool
    {
        global $DB;

        if (isset($item->input['_close']) && $item->input['_close'] == 0) {
            $parentItemtype = isset($item->fields['itemtype']) ? $item->fields['itemtype'] : null;
            $parentItemsId  = isset($item->fields['items_id']) ? $item->fields['items_id'] : null;

            $config = PluginYagpConfig::getInstance();

            $is_table = ITILSolution::getTable();
            $query = [
                'FROM' => $is_table,
                'WHERE' => [
                    //'solutiontypes_id'  => $config->fields['solutiontypes_id_rejected'],
                    'itemtype'          => $parentItemtype,
                    'items_id'          => $parentItemsId,
                    //'status'            => CommonITILValidation::REFUSED
                ],
                'ORDER' => 'id DESC',
                'LIMIT' => '1'
            ];

            $iterator = $DB->request($query);
            $solution = $iterator->current();
            if (
                isset($item->input['requesttypes_id']) &&
                $item->input['requesttypes_id'] != $config->fields['requesttypes_id_reopen'] &&
                count($iterator) > 0 &&
                $solution['solutiontypes_id'] == $config->fields['solutiontypes_id_rejected'] &&
                $solution['status'] == CommonITILValidation::REFUSED
            ) {
                if ($parentItemtype == Ticket::class) {
                    $ticket = new $parentItemtype();
                    $ticket->getFromDB($parentItemsId);
                    $ticket->update(['id' => $parentItemsId, 'status' => Ticket::CLOSED]);
                }
            }
        }

        return true;
    }

    /**
     * autocloseTicket
     *
     * @param  mixed $solution
     * @return bool
     */
    public static function autocloseTicket(ITILSolution $solution): bool
    {
        $solution->update([
            'id'                => $solution->fields['id'],
            'status'            => CommonITILValidation::ACCEPTED,
            'date_approval'     => date("Y-m-d H:i:s"),
            'users_id_approval' => $solution->fields['users_id']
        ]);

        if ($solution->fields['itemtype'] == Ticket::class) {
            $itemtype = new $solution->fields['itemtype']();
            $itemtype->getFromDB($solution->fields['items_id']);
            $itemtype->update([
                'id'     => $solution->fields['items_id'],
                'status' => Ticket::CLOSED
            ]);

            return true;
        }

        return false;
    }

    public static function plugin_yagp_postItemForm($params)
    {
        if (isset($params['item']) && $params['item'] instanceof CommonDBTM) {
            switch (get_class($params['item'])) {
                case 'Ticket':
                    if ($params['item']->getID()) {
                        $id = $params['item']->getID();

                        $ticket = new Ticket();
                        $ticket->getFromDB($id);

                        $ticket_cat = new self();
                        $ticket_cat->getFromDBByCrit(["tickets_id" => $id]);
                        if (!empty($ticket_cat->fields)) {
                            if ($ticket_cat->fields["plugin_yagp_itilcategories_id"] !== $ticket->fields["itilcategories_id"]) {
                                $cat = new ITILCategory();
                                $cat->getFromDB($ticket_cat->fields["plugin_yagp_itilcategories_id"]);
                                if (!empty($cat->fields)) {
                                    $cat_name = $cat->fields["name"];
                                    $script = <<<JAVASCRIPT
                                    $(document).ready(function(){
                                        if( $('#recategorized').length ==0)  {
                                            $("span[id^='category_block_']").after("<div id='recategorized' class='form-field row col-12 d-flex align-items-center mb-2'><label class='col-form-label col-xxl-4 text-xxl-end'>" + __("Initial category","yagp") + "</label>"+'<div class="col-xxl-8  field-container"><span class="entity-badge" title="techs-tickets"><span class="text-nowrap">'+"{$cat_name}"+'</span></span></div>'+"</div>");
                                        }
                                    });
JAVASCRIPT;
                                    echo Html::scriptBlock($script);
                                } else {
                                    $cat_name = __("without");
                                    $script = <<<JAVASCRIPT
                                    $(document).ready(function(){
                                        if( $('#recategorized').length ==0)  {
                                            $("span[id^='category_block_']").after("<div id='recategorized' class='form-field row col-12 d-flex align-items-center mb-2'><label class='col-form-label col-xxl-4 text-xxl-end'>" + __("Initial category","yagp") + "</label>"+'<div class="col-xxl-8  field-container"><span class="entity-badge" title="techs-tickets"><span class="text-nowrap">'+"{$cat_name}"+'</span></span></div>'+"</div>");
                                        }
                                    });
JAVASCRIPT;
                                    echo Html::scriptBlock($script);
                                }
                            }
                        }
                    }
                    break;
            }
        }
    }

    public static function plugin_yagp_preShowItem($params)
    {
        $config = PluginYagpConfig::getInstance();
        if (
            $config->fields['hide_historical'] &&
            $_SESSION["glpiactiveprofile"]["interface"] == "helpdesk"
        ) {
            if (isset($params['item']) && $params['item'] instanceof CommonDBTM) {
                switch (get_class($params['item'])) {
                    case 'Ticket':
                        $script = <<<JAVASCRIPT
$(document).ready(function() {
    console.log($("a[data-bs-target^='#tab-Log']").get());
    $("a[data-bs-target^='#tab-Log']").css({display:"none"});
});
JAVASCRIPT;

                        echo Html::scriptBlock($script);
                }
            }
        }
    }

    /**
     * install
     *
     * @param  mixed $migration
     * @return void
     */
    public static function install(Migration $migration): void
    {
        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = self::getTable();
        if (!$DB->tableExists($table)) {
            $migration->displayMessage("Installing $table");
            $query = "CREATE TABLE IF NOT EXISTS `$table` (
                `id` int {$default_key_sign} NOT NULL auto_increment,
                `tickets_id` INT {$default_key_sign} NOT NULL,
                `plugin_yagp_itilcategories_id` INT {$default_key_sign} NOT NULL,
                `is_recategorized` tinyint NOT NULL DEFAULT '1',
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`tickets_id`),
                KEY `tickets_id` (`tickets_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET={$default_charset}
                COLLATE={$default_collation} ROW_FORMAT=DYNAMIC;";
            $DB->query($query) or die($DB->error());
        }
    }
}
