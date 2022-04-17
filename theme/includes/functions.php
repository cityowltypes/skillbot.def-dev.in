<?php
namespace Wildfire\Theme;

/*
Call other classes from Wildfire\Core.
Uncomment MySQL or Admin classes if you use their functions.
 */
use Wildfire\Core\MySQL as MySQL;
//use Wildfire\Core\Admin as Admin;
use Wildfire\Core\Dash as Dash;

use BotMan\BotMan\BotMan;
use BotMan\BotMan\BotManFactory;
use BotMan\BotMan\Drivers\DriverManager;
use BotMan\Drivers\Telegram\Extensions;
use BotMan\Drivers\Telegram\Extensions\Keyboard;
use BotMan\Drivers\Telegram\Extensions\KeyboardButton;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class Functions {

    public function get_youtube_id($link) {

        $regexstr = '~
            # Match Youtube link and embed code
            (?:                             # Group to match embed codes
                (?:<iframe [^>]*src=")?     # If iframe match up to first quote of src
                |(?:                        # Group to match if older embed
                    (?:<object .*>)?        # Match opening Object tag
                    (?:<param .*</param>)*  # Match all param tags
                    (?:<embed [^>]*src=")?  # Match embed tag to the first quote of src
                )?                          # End older embed code group
            )?                              # End embed code groups
            (?:                             # Group youtube url
                https?:\/\/                 # Either http or https
                (?:[\w]+\.)*                # Optional subdomains
                (?:                         # Group host alternatives.
                youtu\.be/                  # Either youtu.be,
                | youtube\.com              # or youtube.com
                | youtube-nocookie\.com     # or youtube-nocookie.com
                )                           # End Host Group
                (?:\S*[^\w\-\s])?           # Extra stuff up to VIDEO_ID
                ([\w\-]{11})                # $1: VIDEO_ID is numeric
                [^\s]*                      # Not a space
            )                               # End group
            "?                              # Match end quote if part of src
            (?:[^>]*>)?                     # Match any extra stuff up to close brace
            (?:                             # Group to match last embed code
                </iframe>                   # Match the end of the iframe
                |</embed></object>          # or Match the end of the older embed
            )?                              # End Group of last bit of embed code
            ~ix';

        preg_match($regexstr, $link, $matches);

        return $matches[1];

    }

    public function derephrase($rephrase_string, $use_handle_slug=0, $handle_slug_preset_array=[]) {

        if (strstr($rephrase_string, '##')) {
            $reph_array = array_values(
                                array_filter ( 
                                    array_map( 'trim', 
                                        explode('##', $rephrase_string))
                                )
                            );

            $i=0;
            foreach ($reph_array as $reph) {
                if (strstr($reph, '::')) {
                    $reph_temp = array_values(
                            array_filter ( 
                                array_map( 'trim', 
                                    explode('::', $reph))
                            )
                        );
                    
                    if ($use_handle_slug) {
                        if ($handle_slug_preset_array)
                            $handle = $handle_slug_preset_array[$i];
                        else {
                            $handle = $reph_temp[0];
                            array_shift($reph_temp);
                        }
                    }
                    else
                        $handle = $i;

                    if (count($reph_temp)>1) {
                        foreach ($reph_temp as $rtemp) {
                            $reph_available[$handle][] = $rtemp;
                        }
                    } else {
                        $reph_available[$handle] = $reph_temp[0];
                    }
                }
                else {
                    $reph_available[$i] = $reph;
                }

                $i++;
            }

            return $reph_available;
        }
        else 
            return array($rephrase_string);

    }

    public function get_response_id($chatbot_slug, $telegram_user_id) {
        $sql = new MySQL;
        return $sql->executeSQL("SELECT `id` FROM `data` WHERE `content_privacy`='private' AND `content`->'$.telegram_user_id' = ".$telegram_user_id." AND `content`->'$.chatbot' = '$chatbot_slug' AND `content`->'$.type' = 'response'")[0]['id'];
    }

    public function save_response($chatbot_slug, $telegram_user_id, $last_message_identifier='', $response=[]) {
        $dash = new Dash;

        $obj = array();
        
        $response_id = $this->get_response_id($chatbot_slug, $telegram_user_id);

        if ($response_id && $last_message_identifier) {
            $this->save_response_attr($response_id, $last_message_identifier, $response['text']);
        }
        else {
            $obj['title'] = $chatbot_slug.' '.$telegram_user_id;
            $obj['type']='response';
            $obj['content_privacy']='private';
    
            if ($response)
                $obj['last_response']=$response;
    
            $obj['chatbot']=$chatbot_slug;
            $obj['telegram_user_id']=$telegram_user_id;
            $response_id = $dash->pushObject($obj);
        }

        return $response_id;

    }

    public function save_response_attr($response_id, $last_message_identifier='', $response='') {
        $dash = new Dash;
        
        $dash->pushAttribute($response_id, 'last_message_identifier', $last_message_identifier);

        if ($response)
            $id = $dash->pushAttribute($response_id, $last_message_identifier, $response);
        else
            $id = $dash->pushAttribute($response_id, $last_message_identifier, '##');

        return $id;

    }

    public function send_message($message, $api_token='', $channel='telegram') {

        if ($channel=='telegram') {
            $config = [
                // Your driver-specific configuration
                 "telegram" => [
                    "token" => $api_token
                 ]
            ];

            // Load the driver(s) you want to use
            DriverManager::loadDriver(\BotMan\Drivers\Telegram\TelegramDriver::class);
            // Create an instance
            $botman = BotManFactory::create($config);

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

            return true;
        }

        else {
            return false;
        }
    }

    public function get_next_message_identifier($chatbot_id, $last_message_identifier, $language='en', $previous_response='', $response_id=0) {
        $dash = new Dash;

        $last_message_response_options = json_decode($dash->getAttribute($response_id, 'last_message_response_options'), true);
        $last_message_response_key = array_search($previous_response, $last_message_response_options);

        if (!$last_message_identifier)
            $last_message_identifier = 'lang';

        if ($last_message_response_key) {
            if ($last_message_response_key == 'lang')
                return 'lang';
            else if ($last_message_identifier == 'lang')
                return $chatbot_id.'##intro_message';
            else if ($last_message_identifier == $chatbot_id.'##intro_message' || $last_message_response_key == 'menu')
                return 'menu';
            else 
                return 'id##'.$last_message_response_key;
        }
        else
            return $last_message_identifier;
    }

    public function get_message($message_identifier, $chatbot_id, $language='en') {
        $dash = new Dash;
        $language = trim(strtolower($language));
        $telegram_message = array();

        $chatbot = $dash->getObject($chatbot_id);

        if ($message_identifier == 'lang') {
            $telegram_message['message'] = 'Choose language';
            $telegram_message['response'] = $this->derephrase($chatbot['languages'], 1);
        }

        else if ($message_identifier == $chatbot_id.'##intro_message') {
            $telegram_message['message'] = $this->derephrase($chatbot['intro_message'])[0];
            $telegram_message['response'] = array('menu'=>'Main Menu');
        }

        else if ($message_identifier == 'menu') {
            $telegram_message['message'] = 'Main menu';
            
            $menu_items = $this->derephrase($chatbot['module_and_form_ids'], 1);
            $arr = array();

            foreach ($menu_items as $module_id=>$assessment_form_id) {
                if (!$module_id || $module_id=='0') {
                    $temp = $dash->getAttribute($assessment_form_id, 'title');
                    $arr[$assessment_form_id] = $this->derephrase($temp)[0];
                }
                else {
                    $temp = $dash->getAttribute($module_id, 'title');
                    $arr[$module_id] = $this->derephrase($temp)[0];
                }
            }

            $telegram_message['response'] = $arr;
            $telegram_message['response']['lang'] = '<change language>';
        }

        else if (substr($message_identifier, 0, 4)=='id##') {
            $chain_of_ids = $this->derephrase($message_identifier);
            $message = $dash->getObject($chain_of_ids[1]);
            $telegram_message['message'] = $message['title'];
            $telegram_message['response']['next'] = 'Next Slide';
            $telegram_message['response']['prev'] = 'Prev Slide';
            $telegram_message['response']['menu'] = '<back to menu>';
            $telegram_message['response']['lang'] = '<change language>';
        }

        else {
            $telegram_message['message'] = 'Invalid response identifier - '.$message_identifier;
            $telegram_message['response'] = array('menu');
        }

        return $telegram_message;
    }

    public function get_message_array($message_identifier) {
        $dash = new Dash;
        $chain_of_ids = $this->derephrase($message_identifier);
        $obj = $dash->getObject($chain_of_ids[1]);

        if ($obj['type']=='chatbot') {
            if ($obj['intro_message']) {
                $list_of_messages['intro_message'] = $obj['intro_message'];
            }
            
            $items = $this->derephrase($obj['module_and_form_ids'], 1);

            foreach ($items as $module_id=>$assessment_form_id) {
                if ($module_id) {
                    if ($title = trim($dash->getAttribute($module_id, 'title'))) {
                        $list_of_messages['id##'.$module_id] = $title;
                    }
                }
                else if ($assessment_form_id) {
                    if ($title = trim($dash->getAttribute($assessment_form_id, 'title'))) {
                        $list_of_messages['id##'.$assessment_form_id] = $title;
                    }
                }
            }

            if ($obj['end_message']) {
                $list_of_messages['end_message'] = $obj['end_message'];
            }

            return $list_of_messages;
        }

        if ($obj['type']=='module') {
            if ($obj['intro_message']) {
                $list_of_messages['intro_message'] = $obj['intro_message'];
            }
            
            $items = $this->derephrase($obj['level_and_form_ids'], 1);

            foreach ($items as $level_id=>$assessment_form_id) {
                if ($level_id) {
                    if ($title = trim($dash->getAttribute($level_id, 'title'))) {
                        $list_of_messages['id##'.$level_id] = $title;
                    }
                }
                else if ($assessment_form_id) {
                    if ($title = trim($dash->getAttribute($assessment_form_id, 'title'))) {
                        $list_of_messages['id##'.$assessment_form_id] = $title;
                    }
                }
            }

            if ($obj['end_message']) {
                $list_of_messages['end_message'] = $obj['end_message'];
            }

            return $list_of_messages;
        }

        if ($obj['type']=='level') {
            if ($obj['intro_message']) {
                $list_of_messages['intro_message'] = $obj['intro_message'];
            }
            
            $items = array_map('trim', explode(',', $obj['chapter_ids']));

            foreach ($items as $chapter_id) {
                if ($title = trim($dash->getAttribute($chapter_id, 'title'))) {
                    $list_of_messages['id##'.$chapter_id] = $title;
                }
            }

            if ($obj['end_message']) {
                $list_of_messages['end_message'] = $obj['end_message'];
            }

            return $list_of_messages;
        }

        if ($obj['type']=='chapter') {

            $i=0;
            foreach ($obj['messages'] as $message) {
                if (trim($message)) {
                    $list_of_messages['id##'.$obj['id'].'##'.$i] = $message;
                    $i++;
                }
            }

            return $list_of_messages;
        }

        if ($obj['type']=='form') {

            if ($obj['intro_message']) {
                $list_of_messages['intro_message'] = $obj['intro_message'];
            }

            $i=0;
            foreach ($obj['questions'] as $question) {
                if (trim($question)) {
                    $list_of_messages['id##'.$obj['id'].'##'.$i] = $question;
                    $i++;
                }
            }

            if ($obj['end_message'])
                $list_of_messages['end_message'] = $obj['end_message'];

            return $list_of_messages;
        }
    }
}