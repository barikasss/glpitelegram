<?php
/*
  -------------------------------------------------------------------------
  TelegramBot plugin for GLPI
  Copyright (C) 2017 by the TelegramBot Development Team.

  https://github.com/pluginsGLPI/telegrambot
  -------------------------------------------------------------------------

  LICENSE

  This file is part of TelegramBot.

  TelegramBot is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  TelegramBot is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with TelegramBot. If not, see <http://www.gnu.org/licenses/>.
  --------------------------------------------------------------------------
 */

use Longman\TelegramBot\Request;

class PluginTelegrambotTicket extends CommonDBTM
{

   /**
    * Add a new Ticket via Telegrambot. ✔
    *
    * @param array $text
    * @return boolean|string
    */
   static public function newTicket($chat_id, $user_chat, $text)
   {
      $glpi_user = PluginTelegrambotUser::getGlpiUserByTelegramUsername($user_chat);
      $glpi_user_id = $glpi_user['id'];
      $glpi_org = $glpi_user['entities_id'];

      if (!$glpi_user_id) {
         return 'Ваш пользователь не найден. Вы не можете создать новую заявку.';
      }

      if ($glpi_org == '') {
         return 'Вы не можете создавать заявки, вы не закреплены за организацией. id организации - ' . $glpi_org;
      }

      $data = [
         "id" => "0",
         "_skip_default_actor" => "1",
         "_tickettemplate" => "1",
         "_predefined_fields" => "W10=",
         "name" => "",
         "content" => "<p>" . $text['description'] . "</p>",
         "entities_id" => "". $glpi_org,
         "date" => "NULL",
         "type" => "2",
         "itilcategories_id" => "" . $text['itilcategories_id'],
         "status" => "1",
         "requesttypes_id" => "4",
         "urgency" => "3",
         "impact" => "3",
         "priority" => "3",
         "locations_id" => "0",
         "actiontime" => "0",
         "validatortype" => "0",
         "_add_validation" => "0",
         "_actors" => [
            "requester" => [
               [
                  "itemtype" => "User",
                  "items_id" => "" . $glpi_user_id,
                  "use_notification" => 0,
                  "alternative_email" => ""
               ]
            ],
            "observer" => [],
            "assign" => []
         ],
         "_notifications_actorname" => "",
         "_notifications_actortype" => "",
         "_notifications_actorindex" => "",
         "_notifications_alternative_email" => "",
         "my_items" => "",
         "itemtype" => "0",
         "items_id" => "0",
         "time_to_own" => "NULL",
         "slas_id_tto" => "0",
         "time_to_resolve" => "NULL",
         "slas_id_ttr" => "0",
         "internal_time_to_own" => "NULL",
         "olas_id_tto" => "0",
         "internal_time_to_resolve" => "NULL",
         "olas_id_ttr" => "0",
         "_link" => [
            "tickets_id_1" => "0",
            "link" => "1",
            "tickets_id_2" => "0"
         ],
         "add" => ""
      ];

      $track = new Ticket();

      if ($track->add($data)) {
         return 'Заявка зарегистрирована.';
      } else {
         return 'Ошибка при вставке данных пользователя заявки.';
      }
   }

   /**
    * Search for a ticket via Telegrambot. ✔
    *
    * @param string $text
    * @return boolean|string
    */
   static public function searchTicket($text)
   {
      $ticket_id = (int)$text;

      if ($text === '' || $ticket_id == 0) {
         return false;
      }

      $response = 'Ticket ' . $text . ' not found.';

      $ticket_data = self::getTicketData($ticket_id);

      if ($ticket_data) {
         $ticket_id = $ticket_data['id'];
         $ticket_title = $ticket_data['name'];
         $ticket_description = strip_tags(html_entity_decode($ticket_data['content']));

         $response = <<<RESPONSE
Ticket search result:
ID: {$ticket_id}
Title: {$ticket_title}
Description: {$ticket_description}
RESPONSE;
      }

      return $response;
   }

   /**
    * Add a new followup to a Ticket via Telegrambot. ✔
    *
    * @param int $chat_id
    * @param string $user_chat
    * @param string $text
    * @return boolean|string
    * @global object $DB
    */
   static public function newFollowup($chat_id, $user_chat, $text)
   {

      if ($text === '' || strpos($text, '**') === false) {
         return false;
      }

      $text_parts = explode('**', $text);
      $ticket_id = (int)trim($text_parts[0]);
      $followup_text = trim($text_parts[1]);

      if ($ticket_id == 0 || empty($followup_text)) {
         return false;
      }

      $ticket_data = self::getTicketData($ticket_id);
      if (!$ticket_data) {
         return "Ticket ID $ticket_id not found.";
      }

      $glpi_user = PluginTelegrambotUser::getGlpiUserByTelegramUsername($user_chat);
      $glpi_user_id = $glpi_user['id'];
      if (!$glpi_user_id) {
         return "Your user was not found. You can not create a new followup.";
      }

      global $DB;

      $followup_text = "<p>$followup_text</p>";

      $table = 'glpi_itilfollowups';

      $params = [
         'itemtype' => 'Ticket',
         'items_id' => $ticket_id,
         'date' => date("Y-m-d H:i:s"),
         'users_id' => $glpi_user_id,
         'content' => htmlentities($followup_text),
         'requesttypes_id' => 1,
         'date_mod' => date("Y-m-d H:i:s"),
         'date_creation' => date("Y-m-d H:i:s"),
         'timeline_position' => 1,
         'sourceitems_id' => 0,
         'sourceof_items_id' => 0
      ];

      $result = $DB->insert($table, $params);

      if (!$result) {
         return "Your followup could not be saved to the database.";
      }

      return "Your followup was successfully saved to the Ticket $ticket_id.";
   }

   /**
    * Return the ticket data. ✔
    *
    * @param int $ticket_id
    * @return boolean|array
    * @global object $DB
    */
   static private function getTicketData($ticket_id)
   {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => ['glpi_tickets.id', 'glpi_tickets.name', 'glpi_tickets.content'],
         'FROM' => 'glpi_tickets',
         'WHERE' => [
            'id' => $ticket_id
         ]
         , 'LIMIT' => 1
      ]);

      if ($ticket_data = $iterator->current()) {
         return $ticket_data;
      }

      Toolbox::logInFile("notification", "TG test");

      return false;
   }

   public static function getMyTickets($user_chat)
   {
      global $DB;

      $desired_statuses = [1, 2, 3, 4];

      $glpi_user_id = PluginTelegrambotUser::getGlpiUserByTelegramUsername($user_chat)['id'];

      $sql = "
    SELECT DISTINCT
        glpi_tickets.id AS TicketID,
        glpi_tickets.name AS TicketName,
        glpi_tickets.content AS TicketDescription
    FROM
        glpi_tickets
    LEFT JOIN
        glpi_tickets_users AS tu ON glpi_tickets.id = tu.tickets_id
    LEFT JOIN
        glpi_users AS a ON tu.users_id = a.id
    WHERE
        (
            glpi_tickets.users_id_recipient = $glpi_user_id
            OR a.id = $glpi_user_id
        )
        AND glpi_tickets.status IN (" . implode(',', $desired_statuses) . ")
    ORDER BY
        glpi_tickets.date ASC
";

      $result = $DB->query($sql);

      $tickets = [];
      while ($row = $DB->fetchRow($result)) {
         $tickets[] = [
            'id' => $row[0],
            'name' => $row[1],
            'content' => $row[2]
         ];
      }

      return count($tickets) > 0 ? $tickets : false;
   }

}
