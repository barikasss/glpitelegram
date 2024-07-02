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

use ITILCategory;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Request;

/**
 * User "/newticket" command
 */
class NewticketCommand extends UserCommand
{
   /**
    * @var string
    */
   protected $name = 'newticket';

   public static $commandName = 'newticket';
   /**
    * @var string
    */
   protected $description = 'Создание заявки GLPI';

   /**
    * @var string
    */
   protected $usage = '/newticket';

   /**
    * @var string
    */
   protected $version = '1.0.0';

   /**
    * Command execute method
    *
    * @return \Longman\TelegramBot\Entities\ServerResponse
    * @throws \Longman\TelegramBot\Exception\TelegramException
    */
   public function execute()
   {
      $message = $this->getMessage();
      $chat_id = $message->getChat()->getId();

      $category = new ITILCategory();
      $categories = $category->find(['level' => 1], "completename ASC, level ASC, id ASC");

      $inlineKeyboardButtons = [];

      foreach ($categories as $item) {
         $buttonText = $item['name'];
         $buttonCallback = "category.-.${item['id']}";

         $inlineKeyboardButtons[] = [new InlineKeyboardButton([
            'text' => $buttonText,
            'callback_data' => $buttonCallback,
         ])];
      }

      $inlineKeyboard = new InlineKeyboard(['inline_keyboard' => $inlineKeyboardButtons]);

      $text = 'Выберите категорию заявки';

      return Request::sendMessage([
         'chat_id' => $chat_id,
         'text' => $text,
         'reply_markup' => $inlineKeyboard,
      ]);
   }
}
