<?php
use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\Extensions;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use \Wildfire\Api;
use \Wildfire\Core\Console;
//use BotMan\BotMan\Cache\ArrayCache;
//use BotMan\BotMan\Storages\Drivers\FileStorage;
//use Symfony\Component\HttpFoundation\Request;


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

$message = array();
$message['message'] = "hello";
$message['image'] = "https://wildfiretech.co/theme/assets/img/logo-dark.png";
$message['response'] = ['hello1', 'hello2'];

$message['is_image'] = false;

    
    $botman->custom_msg = $message;
    
    $botman->hears('', function ($bot) {
        $kb = Keyboard::create()
                ->type(Keyboard::TYPE_KEYBOARD)
                ->oneTimeKeyboard()
                ->resizeKeyboard();
        foreach ($bot->custom_msg['response'] as $response){
            $kb = $kb->addRow(KeyboardButton::create($response));
        }
        
        $kb = $kb->toArray();
        if ($bot->custom_msg['is_image'] == true) {
            $attachment = new Image($bot->custom_msg['image']);
            
    // Build message object
            $message = OutgoingMessage::create('')
                ->withAttachment($attachment);
        }
        else{
            $message = $bot->custom_msg['message'];
        }
        $bot->reply($message, $kb);

    });

$botman->listen();
?>