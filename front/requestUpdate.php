<?php

include('../../../inc/includes.php');

$user = User::getById(Session::getLoginUserID());
$user->input['telegram_username'] = $_POST['telegram_username'];
PluginTelegrambotUser::item_update_user($user);
Html::back();
