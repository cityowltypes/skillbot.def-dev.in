<?php
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\Extensions;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use \Wildfire\Api;

$dash = new \Wildfire\Core\Dash();
$functions = new \Wildfire\Theme\Functions();
$api = new Api;

$arr = array();

$arr['content'] = $api->body();
$decoded = json_decode($json, true);
$dash->pushObject($arr);
$config = [
    // Your driver-specific configuration
     "telegram" => [
        "token" => "5118144192:AAH-koRFGMHOX1shd29wZWh2CMH08Tji4zw"
     ]
];

// Load the driver(s) you want to use
DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramDriver::class);

// Create an instance
$botman = BotManFactory::create($config);
//print_r($botman->getUser());
// Give the bot something to listen for.
$botman->hears('hi', function (BotMan $bot) {
    //$kb = new Keyboard;
    //$bt1 = new KeyboardButton('btn1');
    //$bt2 = new KeyboardButton('btn2');
    $kb=Keyboard::create()
                ->type(Keyboard::TYPE_KEYBOARD)
                ->oneTimeKeyboard()
                ->resizeKeyboard()
                ->addRow(KeyboardButton::create('Contratulations!'), KeyboardButton::create('Try again :('))
                ->addRow(KeyboardButton::create('Contratulations!'), KeyboardButton::create('Try again :('))
                ->toArray();
    $bot->reply('Hello yourself.', $kb);
});

// Start listening
$botman->listen();
?>

