<?php

/**
 * This file is part of the PHP Telegram Bot example-bot package.
 * https://github.com/php-telegram-bot/example-bot/
 *
 * (c) PHP Telegram Bot Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Generic message command
 *
 * Gets executed when any type of message is sent.
 *
 * In this service-message-related context, we can handle any incoming service-messages.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Chat;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;

class GenericmessageCommand extends SystemCommand
{
   /**
    * @var string
    */
   protected $name = 'genericmessage';

   /**
    * @var string
    */
   protected $description = 'Handle generic message';

   /**
    * @var string
    */
   protected $version = '1.0.0';

   /**
    * Main command execution
    *
    * @return ServerResponse
    */
   public function execute(): ServerResponse
   {
      $message = $this->getMessage();
      $chat_id = $message->getChat()->getId();

      $response = Request::getChat([
         'chat_id' => $chat_id,
      ]);

      $text = json_decode($response->toJson(), true)['result']['pinned_message']['text'];
      $text = str_replace('create:1', '~', $text);

      if ($text[0] === '~') {
         $id = explode(' ', $text)[1];

         $message_text = $message->getText();
         $title = $message_text;

         if (mb_strlen($title) > 120) {
            $title = mb_substr($title, 0, 117) . "...";
         }

         $response = \PluginTelegrambotTicket::newTicket($chat_id, $message->getFrom()->getUsername(), [
            'title' => $title,
            'description' => $message_text,
            'itilcategories_id' => $id
         ]);

         Request::unpinChatMessage([
            'chat_id' => $chat_id,
         ]);

         return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => $response,
            'reply_markup' => null,
         ]);
      } else {
         return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => 'Вы не выбрали команду. Выберите команду из меню или напишите её в чате',
            'reply_markup' => null,
         ]);

      }
   }
}
