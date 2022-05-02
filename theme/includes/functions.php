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

    public function derephrase($rephrase_string, $use_handle_slug=0, $handle_slug_preset_array=[], $return_with_favs=0) {

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
                            if (substr($rtemp, -2) == '**' && $return_with_favs) {
                                $this_fav = trim(substr($rtemp, 0, -2));
                                $favs[] = $this_fav;
                                $reph_available[$handle][] = $this_fav;
                            }
                            else
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

            if ($return_with_favs) {
                $rephrased['arr'] = $reph_available;
                $rephrased['fav'] = $favs;
                return $rephrased;
            }
            else
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
        $chatbot = $dash->getObject($chatbot_id);
        $response = $dash->getObject($response_id);

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

            $i = 0;
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

                $i++;
            }

            $telegram_message['response']['lang##'.$obj['id']] = '<change language>';
            return $telegram_message;
        }

        else if ($obj['type']=='module') {

            $module_and_form_ids = $this->derephrase($chatbot['module_and_form_ids'], 1);
            $assessment_form_id_for_this_module = $module_and_form_ids[$obj['id']];

            $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['intro_message'])[0], $api_token);

            if ($assessment_form_id_for_this_module) {
                if ($title = trim($dash->getAttribute($assessment_form_id_for_this_module, 'title'))) {
                    $telegram_message['response']['id##'.$assessment_form_id_for_this_module] = 'Pre-assessment';
                }
            }

            $items = $this->derephrase($obj['level_and_form_ids'], 1);

            foreach ($items as $level_id=>$assessment_form_id) {
                if ($level_id) {
                    if ($title = trim($dash->getAttribute($level_id, 'title'))) {
                        $telegram_message['response']['id##'.$level_id] = '👉👉👉';
                    }
                }
            }
            
            $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            $dash->pushAttribute($response_id, 'last_module_id', $obj['id']);
            return $telegram_message;
        }

        else if ($obj['type']=='level') {
            $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['intro_message'])[0], $api_token);
            
            $items = array_map('trim', explode(',', $obj['chapter_ids']));

            foreach ($items as $chapter_id) {
                if ($title = trim($dash->getAttribute($chapter_id, 'title'))) {
                    $telegram_message['response']['id##'.$chapter_id] =$this->derephrase($title)[0];
                }
            }

            $last_module_id = $response['last_module_id'];
            $last_module_level_and_form_ids = $this->derephrase($dash->getAttribute($last_module_id, 'level_and_form_ids'), 1);
            $assessment_form_id_for_this_level = $last_module_level_and_form_ids[$obj['id']];
            if ($title = trim($dash->getAttribute($assessment_form_id_for_this_level, 'title'))) {
                $telegram_message['response']['id##'.$assessment_form_id_for_this_level] = 'Post-assessment';
            }
            
            $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            $dash->pushAttribute($response_id, 'last_level_id', $obj['id']);
            $dash->pushAttribute($response_id, 'last_assessment_id', $assessment_form_id_for_this_level);
            return $telegram_message;
        }

        else if ($obj['type']=='chapter') {
            if (count($chain_of_ids)==2) {
                $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['title'])[0], $api_token);
                $i = 1;
            }
            else {
                $i = ($chain_of_ids[2] ?? 1) - 1;

                if ($this->derephrase($obj['messages'][$j]))
                    $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['messages'][$i])[0], $api_token);
                else
                    $telegram_message['message'] = '👉👉👉';

                $i = ($chain_of_ids[2] ?? 1) + 1;
            }

            $last_level_id = $response['last_level_id'];
            $last_assessment_id = $response['last_assessment_id'];

            if (trim($telegram_message['message']))
                $telegram_message['response']['id##'.$obj['id'].'##'.($i ?? '1')] = '👉👉👉';
            else {
                $items = array_map('trim', explode(',', $dash->getAttribute($last_level_id, 'chapter_ids')));
                $k = array_search($obj['id'], $items);
                $k = $k + 1;

                if ($title = $dash->getAttribute($items[$k], 'title')) {
                    $next_chapter_id = $items[$k];
                    $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($title)[0], $api_token);
                    $telegram_message['response']['id##'.$next_chapter_id.'##'.($i ?? '1')] = '👉👉👉';
                }
                else if ($title = $dash->getAttribute($last_assessment_id, 'title')) {
                    $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($title)[0], $api_token);
                    $telegram_message['response']['id##'.$last_assessment_id] = '👉👉👉';
                }
                else {
                    $telegram_message['message']=$this->send_multi_message_return_last_one('Back to main menu', $api_token);
                    $telegram_message['response']['id##'.$chatbot_id] = '👉👉👉';
                }

                $i = 1;
            }

            $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            return $telegram_message;
        }

        else if ($obj['type']=='form') {
            $i = ($chain_of_ids[2] ?? 1) - 1;
            $j = $i;

            if (count($chain_of_ids) == 2) {
                if ($obj['intro_message'] ?? false)
                    $telegram_message['message']=$this->send_multi_message_return_last_one(($this->derephrase($obj['intro_message'])[0] ?? 'Let\'s begin'), $api_token);
                else
                    $telegram_message['message']=$this->send_multi_message_return_last_one('Let\'s begin', $api_token);
                $i = 1;

                if (trim($telegram_message['message'])) {
                    $telegram_message['response']['id##'.$obj['id'].'##'.($i ?? '1')] = '👉👉👉';
                }
                
                $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            }

            else if ($obj['questions'][$j]) {
                //$question['fav'][0];

                $arr = $this->derephrase($obj['questions'][$j], 1, [], 1);
                if (array_keys($arr['arr'])[0]) {
                    $question = array_keys($arr['arr'])[0];
                    $response_options = array_values($arr['arr'])[0];
                }
                else if ($arr['arr'][0]) {
                    $question = $arr['arr'][0];
                    $response_options = '-';
                }

                $i = ($chain_of_ids[2] ?? 1) + 1;

                $telegram_message['message']=$this->send_multi_message_return_last_one($question, $api_token);
                $k = 0;

                if (is_array($response_options)) {
                    foreach ($response_options as $val) {
                        $telegram_message['response']['id##'.$obj['id'].'##'.$i.'##'.$k] = $val;
                        $k++;
                    }
                } else if ($response_options == '-') {
                    //$telegram_message['response']['id##'.$chatbot_id] = '';
                    $telegram_message['response']['id##'.$obj['id'].'##'.($i ?? '1')] = '👉👉👉';
                } else if (filter_var(($arr_url = $response_options), FILTER_VALIDATE_URL)) {
                    if ($j == 1) {
                        $arr = array_unique(array_column($this->csv_to_array($arr_url), 'state'));
                    }
                    else if ($j == 2) {
                        $arr = array_combine(array_column($this->csv_to_array($arr_url), 'district'), array_column($this->csv_to_array($arr_url), 'state'));
                        foreach ($arr as $key => $value) {
                            if ($value == $response['id__5__2'])
                                $arr_final[] = $key;
                        }
                        $arr = $arr_final;
                    }
                    else if ($j == 3) {
                        $arr = array_combine(array_column($this->csv_to_array($arr_url), 'village'), array_column($this->csv_to_array($arr_url), 'district'));
                        foreach ($arr as $key => $value) {
                            if ($value == $response['id__5__3'])
                                $arr_final[] = $key;
                        }
                        $arr = $arr_final;
                    }
                    $l = 0;
                    foreach ($arr as $op) {
                        $telegram_message['response']['id##'.$obj['id'].'##'.($i ?? '1').'##'.$l] = $op;
                        $l++;
                    }
                }
                else
                    $telegram_message['response']['id##'.$chatbot_id] = 'Error in Re::phrase 😳';  
            }

            else {
                if ($obj['end_message'] ?? false)
                    $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['end_message'])[0], $api_token);
                else
                    $telegram_message['message']=$this->send_multi_message_return_last_one('Done', $api_token);
                $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            }

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