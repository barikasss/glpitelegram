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

namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

/**
 * User "/searchticket" command
 */
class SearchticketCommand extends UserCommand
{
   /**
    * @var string
    */
   protected $name = 'searchticket';

   /**
    * @var string
    */
   protected $description = 'Отображает все ваши не закрытые заявки';

   /**
    * @var string
    */
   protected $usage = '/searchticket';

   /**
    * @var string
    */
   protected $version = '1.0.0';

   /**
    * Command execute method
    *
    * @throws \Longman\TelegramBot\Exception\TelegramException
    */
   public function execute()
   {
      $message = $this->getMessage();
      $chat_id = $message->getChat()->getId();

      $response = \PluginTelegrambotTicket::getMyTickets($message->getFrom()->getUsername());

      if (!$response) {
         return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => "Нет заявок назначенных на вас",
         ]);
      }
      foreach ($response as $item) {
         $description = $this->deleteHtml($item['content']);;
         $title = $this->deleteHtml($item['name']);

         Request::sendMessage([
            'chat_id' => $chat_id,
            'parse_mode' => 'markdown',
            'text' => "*ID*: ${item['id']}\n*Заголовок*: $title\n*Описание*: $description"
         ]);
      }
   }

   private function deleteHtml($str)
   {
      return trim(strip_tags(html_entity_decode($str)));
   }
}
