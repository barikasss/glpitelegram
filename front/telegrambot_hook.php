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
 *
 */

use Longman\TelegramBot\Request;

include('../../../inc/includes.php');

require GLPI_ROOT . '/plugins/telegrambot/vendor/autoload.php';

$bot_api_key = PluginTelegrambotBot::getConfig('token');
$bot_username = PluginTelegrambotBot::getConfig('bot_username');
$hook_url = 'https://' . $_SERVER['SERVER_NAME'] . '/plugins/telegrambot/front/telegrambot_hook.php';

//$update = json_decode(file_get_contents("php://input"), true);

try {
   // Create Telegram API object
   $telegram = new Longman\TelegramBot\Telegram($bot_api_key, $bot_username);
   $telegram->addCommandsPath(GLPI_ROOT . '/plugins/telegrambot/inc/Commands');

   $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
   // Silence is golden!
   // Log telegram errors
   Longman\TelegramBot\TelegramLog::error($e);
}
