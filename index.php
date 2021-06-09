<?php
date_default_timezone_set('Europe/Moscow');
error_reporting(E_ALL);
ini_set('display_errors', true);

include('vendor/autoload.php');
include('db/DataBase.php');

const API_KEY = '1837231670:AAGrUskRA1KzUZWMbIyheqngO9QxYA_fUZc';
const DB_NAME = 'tu2096_base';
const DB_USER = 'tu2096_user';
const DB_PASS = '(AY%h)Kk{([5';
const COUNT_USER = 15;

use Telegram\Bot\Api;

$telegram = new Api(API_KEY);
$result = $telegram->getWebhookUpdates();
$text = $result["message"]["text"];
$chat_id = $result["message"]["chat"]["id"];
$callback_query = $result['callback_query'];
$data = $callback_query['data'];
$chat_id_in = $callback_query['message']['chat']['id'];
$uid = $result['message']['from']['id'];

$db = DataBase::getDB();
$check = $db->selectCell('SELECT `id` FROM `subscribers` WHERE `uid` = :uid;', ['uid' => $uid]);
if (!$check && $uid) {
    $db->query('INSERT INTO `subscribers` SET `uid` = :uid, `date` = :date;', [
        'uid' => $uid,
        'date' => time(),
    ]);
}

//file_put_contents('log', print_r($result, true), 8);

if ($text) {
    if ($text == "/start") {

        $reply = 'Поздравляем! Получай МАНУАЛ ПО ПРОЕКТИРОВАНИЮ нажав на кнопку "МАНУАЛ" 📘
    💡 Он поможет проектировать комфортные дома и расскажет то, чего не дают в универе!👩‍🎓
    ______________
    С заботой о Вас <a href="https://www.instagram.com/viveya.proekt/">@Viveya.Proekt</a> ';
        $reply_markup = $telegram->replyKeyboardMarkup([
            "inline_keyboard" => [
                [
                    [
                        "text" => "МАНУАЛ",
                        "callback_data" => "/manual"
                    ]
                ]
            ]
        ]);
        $telegram->sendMessage([
            'chat_id' => $chat_id,
            'text' => $reply,
            'reply_markup' => $reply_markup,
            'parse_mode' => 'html'
        ]);
    } elseif ($text == "/subscribers") {
        $count_subscribers = $db->selectCell('SELECT count(`id`) FROM `subscribers` WHERE `active` = 1;') + COUNT_USER;
        $reply = "👥 Подписчиков в чате: " . $count_subscribers;
        $telegram->sendMessage(['chat_id' => $chat_id, 'parse_mode' => 'HTML', 'text' => $reply]);

    } else {
        $reply = "По запросу \"<b>" . $text . "</b>\" ничего не найдено.";
        $telegram->sendMessage(['chat_id' => $chat_id, 'parse_mode' => 'HTML', 'text' => $reply]);
    }
}
if ($data) {
    if ($data == '/manual') {
        $url = "https://runbzopktlwtxhu.cube.ink/TelegramBot/Manual-Arch-Academy.ru.pdf";
        $telegram->sendDocument(['chat_id' => $chat_id_in, 'document' => $url, 'caption' => "Описание."]);
    }
}

$update = $result['my_chat_member'] ?? [];

if (isset($update['new_chat_member']) && $update['new_chat_member']['status'] === 'kicked') {
    $db->query('UPDATE `subscribers` SET `active` = 0, `date_kicked` = :date WHERE `uid` = :uid;', [
        'uid' => $update['from']['id'],
        'date' => time(),
    ]);
}
if (isset($update['new_chat_member']) && $update['new_chat_member']['status'] === 'member') {
    $db->query('UPDATE `subscribers` SET `active` = 1, `date_kicked` = 0 WHERE `uid` = :uid;', [
        'uid' => $update['from']['id'],
    ]);
}
