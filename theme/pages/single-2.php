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



$response = $api->body();
$response = $response['message'];
$user = ['user_id'=>$response['from']['id'], 'slug'=>$slug];
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


$last_message = $functions->get_last_message_sent($user);
/*

//$user['last_message'] = $last_message;
if(!empty($last_message)){
    $functions->set_message($user, $last_message, $response['text']);
}
$next_message = $functions->get_next_message($user);
$functions->send_message($user, $next_message);
$set_last_message_sent($user, $next_message);
*/
?>

