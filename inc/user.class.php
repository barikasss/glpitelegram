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

class PluginTelegrambotUser extends CommonDBTM {

    static public function showUsernameField($item)
    {
        $username = null;

        if ($item->fields['id']) {
            $user = new self();
            $user->getFromDB($item->fields['id']);

            if (!$user->isNewItem()) {
                $username = $user->fields['username'];
            }
        }

        $telegram_username_translation = __('Telegram username');

        $out = <<<HTML
<tr class='tab_bg_1'>
    <td> {$telegram_username_translation} </td>
    <td><input type='text' name='telegram_username' value='{$username}'></td>
</tr>
HTML;

        echo $out;

    }

   static public function showUsernameForm($item)
   {
      $username = null;

      if ($item->fields['id']) {
         $user = new self();
         $user->getFromDB($item->fields['id']);

         if (!$user->isNewItem()) {
            $username = $user->fields['username'];
         }
      }

      $telegram_username_translation = __('Telegram username');
      $csrf = Html::closeForm(false);
      $out = <<<HTML
<form class="mb-3" action="/plugins/telegrambot/front/requestUpdate.php" method="POST">
<tr class='tab_bg_1'>
    <td> {$telegram_username_translation} </td>
    <td><input type='text' name='telegram_username' value='{$username}'></td>
</tr>
<input type="submit" name="update" value="Сохранить" class="btn btn-primary">
$csrf
</form>
HTML;

      echo $out;

   }


   static public function item_add_user(User $item)
    {
        if (isset($item->input['telegram_username'])) {
            $user = new self;
            $user->fields['id'] = $item->fields['id'];
            $user->fields['username'] = $item->input['telegram_username'];
            $user->addToDB();
        }
    }

    static public function item_update_user(User $item)
    {
        $user = new self;
        $user->getFromDB($item->fields['id']);

        if ($user->isNewItem()) {
            self::item_add_user($item);
        } 
        if (isset($item->input['telegram_username'])) {
            $user->fields['username'] = $item->input['telegram_username'];
            $user->updateInDB(['username']);
        }
    }

    static public function userLoginViaTelegrambot($user_chat, $password)
    {
        $token = self::generateTemporaryAccessToken($user_chat, $password);
        $url_access = 'http://' . $_SERVER['SERVER_NAME'] . '/plugins/telegrambot/front/telegrambot_login.php?t=' . $token;
        $access = 'To access GLPI click on this link: ' . $url_access;
        return $access;
    }

    static private function generateTemporaryAccessToken($user_chat, $password)
    {

        $valid_until = [ 'valid_until' => time() + 3600 ];

        $user = self::getGlpiUserByTelegramUsername($user_chat);

        $data_to_token = [
                'tu' => $user_chat, // Telegram username
                'gu' => $user['name'], // GLPI username
                'p' => $password // GLPI password
        ];

        $payload_data = \json_encode($data_to_token);
        $valid_time_data = \json_encode($valid_until);

        $hash = sha1($payload_data . $valid_time_data . $user['id']);

        $token = \base64_encode($valid_time_data) . '.' . \base64_encode($payload_data) . '.' . \base64_encode($hash);

        return $token;

    }

    static public function validateTemporaryAccessToken()
    {

        $split_token_parts = \explode('.', $_GET['t']);

        $token_valid_time   = \json_decode(\base64_decode($split_token_parts[0]), true);
        $token_payload_data = \json_decode(\base64_decode($split_token_parts[1]), true);
        $token_hash         = \base64_decode($split_token_parts[2], true);
//Toolbox::logInFile("notification","validateTemporaryAccessToken token=".json_encode($_GET['t']));

        // Expired Token
        if($token_valid_time['valid_until'] < time()){
            return 'Expired Token!';
        }

        $user = self::getGlpiUserByTelegramUsername($token_payload_data['tu']);

        $hash_to_test = sha1(\base64_decode($split_token_parts[1]) . \base64_decode($split_token_parts[0]) . $user['id']);

        if($token_hash != $hash_to_test){
            return 'Wrong Hash validation!';
        }

        $user_data = [
            'user' => $token_payload_data['gu'],
            'pass' => $token_payload_data['p']
        ];

        return $user_data;
    }

    static public function getGlpiUserByTelegramUsername($user_chat)
    {
        global $DB;

        $user = $DB->request([
            'SELECT' => ['glpi_users.id', 'glpi_users.name', 'glpi_users.entities_id'],
            'FROM' => 'glpi_users',
            'INNER JOIN' => [
                'glpi_plugin_telegrambot_users' => [
                    'FKEY' => [
                        'glpi_plugin_telegrambot_users' => 'id',
                        'glpi_users' => 'id'
                    ]
                ]
            ],
            'WHERE' => [
                'glpi_plugin_telegrambot_users.username' => $user_chat
            ],
            'LIMIT' => 1
        ])->current();

//Toolbox::logInFile("notification","getGlpiUserByTelegramUsername user=".json_encode($user));

        if ($user) {
            return $user;
        }

        return [];

    }

}
