<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Glpi\Http\Response;
use ITILCategory;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Commands\UserCommands\NewticketCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

class CallbackqueryCommand extends SystemCommand
{
   /**
    * @var string
    */
   protected $name = 'callbackquery';

   /**
    * @var string
    */
   protected $description = 'Handle the callback query';

   /**
    * @var string
    */
   protected $version = '1.2.0';

   /**
    * Main command execution
    *
    * @return ServerResponse
    * @throws \Exception
    */
   public function execute(): ServerResponse
   {
      // Callback query data can be fetched and handled accordingly.
      $callback_query = $this->getCallbackQuery();
      $callback_data = $callback_query->getData();
      $chat_id = $callback_query->getMessage()->getChat()->getId();

      $data = explode('.-.', $callback_data);

       switch ($data[0]) {
         case 'category':
            return $this->category($data[1], $chat_id);
         case 'subcategory':
            return $this->subcategory($data[1], $chat_id);
         default:
            break;
      }

      return Request::sendMessage([
         'chat_id' => $chat_id,
         'text' => 'что-то пошло не так обратитесь к администрации',
         'reply_markup' => null,
      ]);
   }

   /**
    * @throws TelegramException
    */
   private function category($id, $chat_id)
   {
      $category = new ITILCategory();
      $categories = $category->find(['level' => 2, 'itilcategories_id' => $id], "completename ASC, level ASC, id ASC");

      $inlineKeyboardButtons = [];

      foreach ($categories as $item) {
         $buttonText = $item['name'];
         $buttonCallback = "subcategory.-.${item['id']}";
         $inlineKeyboardButtons[] = [new InlineKeyboardButton([
            'text' => $buttonText,
            'callback_data' => $buttonCallback,
         ])];
      }

      $inlineKeyboard = new InlineKeyboard(['inline_keyboard' => $inlineKeyboardButtons]);

      $text = 'Выберите подкатегорию:';

      return Request::sendMessage([
         'chat_id' => $chat_id,
         'text' => $text,
         'reply_markup' => $inlineKeyboard,
      ]);
   }

   /**
    * @throws TelegramException
    */
   private function subcategory($id, $chat_id)
   {
      $message = Request::sendMessage([
         'chat_id' => $chat_id,
         'text' => "create:1 $id",
      ]);

      Request::pinChatMessage([
         'chat_id' => $chat_id,
         'message_id' => $message->getResult()->message_id,
      ]);

      return Request::sendMessage([
         'chat_id' => $chat_id,
         'text' => 'Введите описание',
      ]);
   }
}
