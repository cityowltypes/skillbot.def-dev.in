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

    public function csv_to_array($url) {
        $path = str_replace(BASE_URL, ABSOLUTE_PATH, urldecode($url));

        $csv = array_map('str_getcsv', file($path));
        $headers = $csv[0];
        unset($csv[0]);
        $rowsWithKeys = [];
        foreach ($csv as $row) {
            $newRow = [];
            foreach ($headers as $k => $key) {
                $newRow[$key] = $row[$k];
            }
            $rowsWithKeys[] = $newRow;
        }
        return $rowsWithKeys;
    }

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

    public function send_message($message, $api_token='') {

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

    public function get_message_array($message_identifier, $chatbot_id, $language='en', $response_id=0, $api_token='') {
        $dash = new Dash;

        $chain_of_ids = $this->derephrase($message_identifier);
        $obj = $dash->getObject($chain_of_ids[1]);

        if ($chain_of_ids[0] == 'lang') {
            $telegram_message['message'] = 'Choose language';
            $telegram_message['response'] = $this->derephrase($obj['languages'], 1);
            return $telegram_message;
        }

        else if ($obj['type']=='chatbot') {
            if ($obj['intro_message']) {
                $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['intro_message'])[0], $api_token);
            } else {
                $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['title'])[0], $api_token);
            }
            
            $items = $this->derephrase($obj['module_and_form_ids'], 1);

            foreach ($items as $module_id=>$assessment_form_id) {
                if ($module_id) {
                    if ($title = trim($dash->getAttribute($module_id, 'title'))) {
                        $telegram_message['response']['id##'.$module_id] = $this->derephrase($title)[0];
                    }
                }
                else if ($assessment_form_id) {
                    if ($title = trim($dash->getAttribute($assessment_form_id, 'title'))) {
                        $telegram_message['response']['id##'.$assessment_form_id] =$this->derephrase($title)[0];
                    }
                }
            }

            $telegram_message['response']['lang##'.$obj['id']] = '<change language>';
            return $telegram_message;
        }

        else if ($obj['type']=='module') {
            if ($obj['intro_message']) {
                $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['intro_message'])[0], $api_token);
            } else {
                $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['title'])[0], $api_token);
            }

            $items = $this->derephrase($obj['level_and_form_ids'], 1);

            foreach ($items as $level_id=>$assessment_form_id) {
                if ($level_id) {
                    if ($title = trim($dash->getAttribute($level_id, 'title'))) {
                        $telegram_message['response']['id##'.$level_id] = $this->derephrase($title)[0];
                    }
                }
                else if ($assessment_form_id) {
                    if ($title = trim($dash->getAttribute($assessment_form_id, 'title'))) {
                        $telegram_message['response']['id##'.$assessment_form_id] =$this->derephrase($title)[0];
                    }
                }
            }
            
            $telegram_message['response']['id##'.$chatbot_id] = '<main menu>';
            return $telegram_message;
        }

        else if ($obj['type']=='level') {
            if ($obj['intro_message']) {
                $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['intro_message'])[0], $api_token);
            } else {
                $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['title'])[0], $api_token);
            }
            
            $items = array_map('trim', explode(',', $obj['chapter_ids']));

            foreach ($items as $chapter_id) {
                if ($title = trim($dash->getAttribute($chapter_id, 'title'))) {
                    $telegram_message['response']['id##'.$chapter_id] =$this->derephrase($title)[0];
                }
            }
            
            $telegram_message['response']['id##'.$chatbot_id] = '<main menu>';
            $dash->pushAttribute($response_id, 'last_level_id', $obj['id']);
            return $telegram_message;
        }

        else if ($obj['type']=='chapter') {
            if (count($chain_of_ids)==2) {
                $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['title'])[0], $api_token);
                $i=0;
            }
            else {
                $i = $chain_of_ids[2];
                $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['messages'][$i--])[0], $api_token);
                $i++;
            }

            $i++;
            $last_level_id = $dash->getAttribute($response_id, 'last_level_id');

            if (trim($telegram_message['message']))
                $telegram_message['response']['id##'.$obj['id'].'##'.$i] = 'Next';
            $telegram_message['response']['id##'.$last_level_id] = '<list of chapters>';
            return $telegram_message;
        }

        else if ($obj['type']=='form') {
            if (count($chain_of_ids)==2) {
                $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['title'])[0], $api_token);
                $i=0;
            }
            else {
                $i = $chain_of_ids[2];
                $j = $i--;
                $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['questions'][$j])[0], $api_token);
                $i++;
            }

            $i++;

            if (trim($telegram_message['message'])) {
                if ($this->derephrase($obj['response_options'][$j])=='-')
                    $telegram_message['response']['id##'.$obj['id'].'##'.$i] = '';
                else if (filter_var($this->derephrase($obj['response_options'][$j]), FILTER_VALIDATE_URL))
                    $telegram_message['response']['id##'.$obj['id'].'##'.$i] = $this->derephrase($obj['response_options'][$j]);
                else
                    $telegram_message['response']['id##'.$obj['id'].'##'.$i] = 'Next';
            }

            $telegram_message['response']['id##'.$chatbot_id] = '<main menu>';
            return $telegram_message;
        }
    }

    public function send_multi_message_return_last_one($messages, $api_token) {
        if (is_array($messages)) {
            $var = array_pop($messages);
            foreach ($messages as $msg) {
                $this->send_message(array('message'=>$msg), $api_token);
            }
            return $var;
        } else {
            return $messages;
        }
    }
}