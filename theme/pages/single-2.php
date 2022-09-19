<?php include_once __DIR__ . '/../_init.php';
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\Drivers\Telegram\Extensions;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

use \Wildfire\Api;
use \Wildfire\Core\Console;

$api = new Api;

$telegram_response = (array) $api->body()['message'];
$chatbot = $dash->getObject(['type'=>'chatbot', 'slug'=>$slug]);

$config = [
    // Your driver-specific configuration
     "telegram" => [
        "token" => $chatbot['api_token']
     ]
];

// Load the driver(s) you want to use
DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramDriver::class);

// Create an instance
$botman = BotManFactory::create($config);

$question = Keyboard::create()
            ->type(Keyboard::TYPE_KEYBOARD)
            ->oneTimeKeyboard()
            ->resizeKeyboard();

$question = $question->addRow(KeyboardButton::create('Random dog photo')->value('random'));
$question = $question->addRow(KeyboardButton::create('A photo by breed')->value('breed'));

$question = $question->toArray();

$botman->hears('hello', function ($bot) {
    $bot->reply('dolly', $question);
});


$botman->listen();
?>