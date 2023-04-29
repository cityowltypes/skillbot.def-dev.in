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

    public function array_to_csv($data, $fields, $filename) {
        $data=array_merge(array($fields), $data);

        //print_r($data);
        ob_start();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename='.$filename.'.csv');

        $fp = fopen('php://output', 'w');

        // Loop through file pointer and a line
        foreach ($data as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
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
                        $reph_available[$handle] = $reph_temp[0] ?? null;
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
            foreach ($bot->custom_msg['response'] ?? [] as $response){
                $kb = $kb->addRow(KeyboardButton::create($response));
            }

            $kb = $kb->toArray();
            $is_link = filter_var($bot->custom_msg['message'], FILTER_VALIDATE_URL);
            if ($is_link && ($ytid = $this->get_youtube_id($bot->custom_msg['message']))) {
                $message = 'https://youtu.be/'.$ytid;
            }
            else if ($is_link && exif_imagetype($is_link)) {
                $attachment = new Image($bot->custom_msg['message']);

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

    public function get_message_array($message_identifier, $chatbot_id, $language='english', $response_id=0, $api_token='', $main_response_id=0) {
        $dash = new Dash;

        $chain_of_ids = $this->derephrase($message_identifier);
        $obj = $dash->getObject($chain_of_ids[1]);
        $chatbot = $dash->getObject($chatbot_id);
        $response = $dash->getObject($response_id);

        $languages = $this->derephrase($chatbot['languages'], 1);
        $lang_id = array_search(strtolower($language), array_map('strtolower', array_values($languages)));
        if (!$lang_id)
            $lang_id = '0';

        if ($chain_of_ids[0] == 'lang') {
            $telegram_message['message'] = 'Choose language';
            $telegram_message['response'] = $languages;
            return $telegram_message;
        }

        else if ($chain_of_ids[0] == 'cert') {
            $telegram_message['message'] = '👉👉👉 https://skillbot.def-dev.in/tool/download-certificate?chatbot_id='.$chatbot_id.'&response_id='.$response_id;
            $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            return $telegram_message;
        }

        else if ($chain_of_ids[0] == 'multiuser') {
            $telegram_message['message'] = 'Switch user';
            $telegram_message['response']['switchuser##'.$chatbot_id.'##1'] = 'Main user';

            $multiuser_count = $dash->getAttribute($main_response_id, 'multiuser_count');
            if ($multiuser_count ?? false) {
                for ($ijk=2; $ijk <= (int) $multiuser_count; $ijk++) {
                    $telegram_message['response']['switchuser##'.$chatbot_id.'##'.$ijk] = 'User No. '.$ijk;
                }
            }
            $telegram_message['response']['switchuser##'.$chatbot_id.'##new'] = 'Add new user';
            $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            return $telegram_message;
        }

        else if ($chain_of_ids[0] == 'switchuser') {
            $telegram_message['message'] = 'User switched successfully.';
            $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            return $telegram_message;
        }

        else if ($obj['type']=='chatbot') {
            if ($obj['intro_message']) {
                $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['intro_message'])[$lang_id], $api_token);
            } else {
                $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['title'])[$lang_id], $api_token);
            }

            $items = $this->derephrase($obj['module_and_form_ids'], 1);

            $i = 0;
            foreach ($items as $module_id=>$assessment_form_id) {
                if ($module_id) {

                    //it's the user registration form
                    if (!$i) {
                        $number_of_questions_in_user_registration = count(json_decode($dash->getAttribute($module_id, 'questions'), 1));

                        //if last question in registration form is not answered
                        if (!$response['id__'.$module_id.'__'.$number_of_questions_in_user_registration]) {
                            if ($title = trim($dash->getAttribute($module_id, 'title'))) {
                                $telegram_message['response']['id##'.$module_id] = $this->derephrase($title)[$lang_id];
                            }

                            break;
                        }
                        else {
                            if ($title = trim($dash->getAttribute($module_id, 'title'))) {
                                $telegram_message['response']['id##'.$module_id] = '✅ '.$this->derephrase($title)[$lang_id];
                            }
                        }
                    }

                    else if ($title = trim($dash->getAttribute($module_id, 'title'))) {
                        if ($response['completed__'.$module_id] == '1')
                            $module_title_prefix = '✅ ';
                        else if ($module_id == $response['last_module_id'])
                            $module_title_prefix = '➡️ ';
                        else
                            $module_title_prefix = '';

                        $telegram_message['response']['id##'.$module_id] = $module_title_prefix.$this->derephrase($title)[$lang_id];
                    }
                }
                else if ($assessment_form_id) {
                    if ($title = trim($dash->getAttribute($assessment_form_id, 'title'))) {
                        $telegram_message['response']['id##'.$assessment_form_id] = $this->derephrase($title)[$lang_id];
                    }
                }

                $i++;
            }

            $telegram_message['response']['cert##'.$obj['id']] = '<download certificate>';

            $telegram_message['response']['lang##'.$obj['id']] = '<change language>';

            if ($obj['allow_multiuser'] ?? false)
                $telegram_message['response']['multiuser##'.$obj['id']] = '<multi-user>';

            return $telegram_message;
        }

        else if ($obj['type']=='module') {

            $module_and_form_ids = $this->derephrase($chatbot['module_and_form_ids'], 1);
            $assessment_form_id_for_this_module = $module_and_form_ids[$obj['id']];

            $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['intro_message'])[$lang_id], $api_token);

            $number_of_questions_in_assessment_form_id_for_this_module = count(json_decode($dash->getAttribute($assessment_form_id_for_this_module, 'questions'), 1));

            //if last question in assessment form is not answered
            if ($assessment_form_id_for_this_module && !$response['id__'.$assessment_form_id_for_this_module.'__'.$number_of_questions_in_assessment_form_id_for_this_module]) {

                if ($title = trim($dash->getAttribute($assessment_form_id_for_this_module, 'title'))) {
                    $telegram_message['response']['id##'.$assessment_form_id_for_this_module] = ($this->derephrase($chatbot['pre_assessment_word'])[$lang_id] ?? 'Pre-assessment');
                }
            }

            else {
                $items = $this->derephrase($obj['level_and_form_ids'], 1);

                foreach ($items as $level_id=>$assessment_form_id) {
                    if ($level_id) {
                        if ($title = trim($dash->getAttribute($level_id, 'title'))) {
                            $telegram_message['response']['id##'.$level_id] = '👉👉👉';
                        }
                    }
                }
            }

            $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            $dash->pushAttribute($response_id, 'last_module_id', $obj['id']);
            return $telegram_message;
        }

        else if ($obj['type']=='level') {
            $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['intro_message'])[$lang_id], $api_token);

            if (strstr($obj['chapter_ids'], ',')) {
                $items = array_map('trim', explode(',', $obj['chapter_ids']));

                foreach ($items as $chapter_id) {
                    if ($title = trim($dash->getAttribute($chapter_id, 'title'))) {

                        if ($response['completed__'.$chapter_id] == '1')
                            $chapter_title_prefix = '✅ ';
                        else
                            $chapter_title_prefix = '';

                        $telegram_message['response']['id##'.$chapter_id] = $chapter_title_prefix.$this->derephrase($title)[$lang_id];
                    }
                }

                $last_module_id = $response['last_module_id'];
                $last_module_level_and_form_ids = $this->derephrase($dash->getAttribute($last_module_id, 'level_and_form_ids'), 1);
                $assessment_form_id_for_this_level = $last_module_level_and_form_ids[$obj['id']];

                $number_of_questions_in_assessment_form_id_for_this_level = count(json_decode($dash->getAttribute($assessment_form_id_for_this_level, 'questions'), 1));

                //if last question in assessment form is not answered
                if ($assessment_form_id_for_this_level && !$response['id__'.$assessment_form_id_for_this_level.'__'.$number_of_questions_in_assessment_form_id_for_this_level]) {

                    if ($title = trim($dash->getAttribute($assessment_form_id_for_this_level, 'title'))) {
                        $telegram_message['response']['id##'.$assessment_form_id_for_this_level] = ($this->derephrase($chatbot['post_assessment_word'])[$lang_id] ?? 'Post-assessment');
                    }
                }
                //form is either complete or there's no post assessment
                else {
                    //mark LEVEL completed
                    $dash->pushAttribute($response_id, 'completed__'.$obj['id'], '1');

                    //if it's the LAST level in the module AND assessment_form_id_for_this_level is 0, mark MODULE complete
                    $last_level_id = end(array_keys($this->derephrase($dash->getAttribute($response['last_module_id'], 'level_and_form_ids'), 1)));
                    if ($obj['id'] == $last_level_id && (!$assessment_form_id_for_this_level || $assessment_form_id_for_this_level == '0'))
                        $dash->pushAttribute($response_id, 'completed__'.$response['last_module_id'], '1');
                }

                $telegram_message['response']['id##'.$chatbot_id] = '🏠';
                $dash->pushAttribute($response_id, 'last_level_id', $obj['id']);

                if ($assessment_form_id_for_this_level)
                    $dash->pushAttribute($response_id, 'last_assessment_id', $assessment_form_id_for_this_level);

                return $telegram_message;
            }
            else {

                $chapter_and_form_ids = $this->derephrase($obj['chapter_ids'], 1);

                $telegram_message['message'] = $this->send_multi_message_return_last_one($this->derephrase($obj['intro_message'])[$lang_id], $api_token);

                foreach ($chapter_and_form_ids as $chapter_id=>$assessment_form_id) {
                    if ($chapter_id && $assessment_form_id) {
                        if ($title = trim($dash->getAttribute($chapter_id, 'title'))) {

                            if ($response['completed__'.$assessment_form_id] == '1')
                                $chapter_title_prefix = '✅ ';
                            else
                                $chapter_title_prefix = '';

                            $telegram_message['response']['id##'.$chapter_id] = $chapter_title_prefix.$this->derephrase($title)[$lang_id];
                        }
                    }
                    else if ($assessment_form_id) {
                        if ($title = trim($dash->getAttribute($assessment_form_id, 'title'))) {
                            $telegram_message['response']['id##'.$assessment_form_id] = $this->derephrase($title)[$lang_id];
                        }
                    }
                    else {
                        if ($title = trim($dash->getAttribute($chapter_id, 'title'))) {

                            if ($response['completed__'.$chapter_id] == '1')
                                $chapter_title_prefix = '✅ ';
                            else
                                $chapter_title_prefix = '';

                            $telegram_message['response']['id##'.$chapter_id] = $chapter_title_prefix.$this->derephrase($title)[$lang_id];
                        }
                    }
                }

                $telegram_message['response']['id##'.$chatbot_id] = '🏠';
                $dash->pushAttribute($response_id, 'last_level_id', $obj['id']);

                return $telegram_message;
            }
        }

        else if ($obj['type']=='chapter') {
            if (count($chain_of_ids)==2) {
                $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['title'])[$lang_id], $api_token);
                $i = 1;
            }
            else {
                $i = ($chain_of_ids[2] ?? 1) - 1;

                if ($this->derephrase($obj['messages'][$j]))
                    $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['messages'][$i])[$lang_id], $api_token);
                else
                    $telegram_message['message'] = '👉👉👉';

                $i = ($chain_of_ids[2] ?? 1) + 1;
            }

            $last_level_id = $response['last_level_id'];
            $last_assessment_id = $response['last_assessment_id'];

            if (trim($telegram_message['message']))
                $telegram_message['response']['id##'.$obj['id'].'##'.($i ?? '1')] = '👉👉👉';
            else {
                $chapter_ids = $dash->getAttribute($last_level_id, 'chapter_ids');
                if (strstr($chapter_ids, ',')) {
                    $items = array_map('trim', explode(',', $chapter_ids));
                    $k = array_search($obj['id'], $items);
                    $k = $k + 1;
                    $next_chapter_or_form_id = $items[$k];
                } else {
                    $items = $this->derephrase($chapter_ids, 1);
                    $item_keys = array_keys($items);
                    $k = array_search($obj['id'], $item_keys);
                    $items = array_values($items);
                    $next_chapter_or_form_id = $items[$k];
                }

                if ($title = $dash->getAttribute($next_chapter_or_form_id, 'title')) {
                    $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($title)[$lang_id], $api_token);
                    $telegram_message['response']['id##'.$next_chapter_or_form_id.'##1'] = '👉👉👉';

                    //if it is a chapter id, then mark the current one complete
                    //if it is a form, then it's completion will be marked in the respective form

                    if ($dash->getAttribute($next_chapter_or_form_id, 'type') == 'chapter') {
                        $dash->pushAttribute($response_id, 'completed__'.$next_chapter_or_form_id, '1');
                    }

                }
                else if ($title = $dash->getAttribute($last_assessment_id, 'title')) {
                    $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($title)[$lang_id], $api_token);
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
                    $telegram_message['message']=$this->send_multi_message_return_last_one(($this->derephrase($obj['intro_message'])[$lang_id] ?? '👉'), $api_token);
                else
                    $telegram_message['message']=$this->send_multi_message_return_last_one('👉', $api_token);

                $form_score_name = 'id__'.$obj['id'].'__score';
                $dash->pushAttribute($response_id, $form_score_name, '0');

                $i = 1;

                if (trim($telegram_message['message'])) {
                    $telegram_message['response']['id##'.$obj['id'].'##'.($i ?? '1')] = '👉👉👉';
                }

                $telegram_message['response']['id##'.$chatbot_id] = '🏠';
            }

            else if ($obj['questions'][$j]) {
                //$question['fav'][0];

                if (!$j) {
                    $form_score_name = 'id__'.$obj['id'].'__score';
                    $dash->pushAttribute($response_id, $form_score_name, '0');
                }

                $arr = $this->derephrase($obj['questions'][$j], 1, [], 1);
                if (is_numeric(array_keys($arr['arr'])[$lang_id])) {
                    $question = array_values($arr['arr'])[$lang_id];
                    $response_options = '-';
                }
                else if (array_keys($arr['arr'])[$lang_id]) {
                    $question = array_keys($arr['arr'])[$lang_id];
                    $response_options = array_values($arr['arr'])[$lang_id];
                    if (array_values($arr['fav'] ?? [])[$lang_id]) {
                        $dash->pushAttribute($response_id, 'last_question_correct_response', array_values($arr['fav'])[$lang_id]);
                    }
                }
                else if ($arr['arr'][$lang_id]) {
                    $question = $arr['arr'][$lang_id];
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
                } else if ($response_options == 'mobile') {
                    //$telegram_message['response']['id##'.$chatbot_id] = '';
                    $telegram_message['response']['id##'.$obj['id'].'##'.($i ?? '1')] = '👉👉👉';
                } else if (filter_var(($arr_url = $response_options), FILTER_VALIDATE_URL)) {
                    if ($j == 1) {
                        //state
                        $arr = array_unique(array_column($this->csv_to_array($arr_url), 'state'));
                    }
                    else if ($j>1) {
                        //district or village
                        if ($j == 2) {
                            $states = array_column($this->csv_to_array($arr_url), 'state');
                            $districts = array_column($this->csv_to_array($arr_url), 'district');
                            if ($districts ?? false)
                                $arr = array_combine($districts, $states);
                            else {
                                $arr = array_combine(array_column($this->csv_to_array($arr_url), 'location'), array_column($this->csv_to_array($arr_url), 'state'));
                            }
                        }
                        else if ($j == 3)
                            $arr = array_combine(array_column($this->csv_to_array($arr_url), 'village'), array_column($this->csv_to_array($arr_url), 'district'));

                        foreach ($arr as $key => $value) {
                            if ($value == $response['id__'.$obj['id'].'__'.$j])
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
                $arr = $this->derephrase($obj['questions'][0], 1, [], 1);
                if (array_values($arr['fav'] ?? [])[$lang_id]) {

                    //if it's the LAST level post assessment form in the module
                    $last_post_assessment_form_id = end(array_values($this->derephrase($dash->getAttribute($response['last_module_id'], 'level_and_form_ids'), 1)));
                    if ($obj['id'] == $last_post_assessment_form_id) {
                        $dash->pushAttribute($response_id, 'completed__'.$response['last_module_id'], '1');
                        $dash->pushAttribute($response_id, 'completed__'.$response['last_level_id'], '1');
                    }

                    //if it's the LAST chapter post assessment form in the module
                    $chapter_ids = $dash->getAttribute($response['last_level_id'], 'chapter_ids');

                    if (!strstr($chapter_ids, ',')) {
                        $chapter_post_assessment_form_ids = array_values($this->derephrase($chapter_ids, 1));
                        if (in_array($obj['id'], $chapter_post_assessment_form_ids)) {
                            $chapter_post_assessment_form_id = $obj['id'];
                            $dash->pushAttribute($response_id, 'completed__'.$chapter_post_assessment_form_id, '1');

                            //if it's the LAST chapter assessment form in level
                            $last_chapter_assessment_form_id = end($chapter_post_assessment_form_ids);
                            if ($obj['id'] == $last_chapter_assessment_form_id)
                                $dash->pushAttribute($response_id, 'completed__'.$response['last_module_id'], '1');
                                $dash->pushAttribute($response_id, 'completed__'.$response['last_level_id'], '1');
                        }
                    }

                    $telegram_message['message']=$this->send_multi_message_return_last_one(array('Score: '.$response['id__'.$obj['id'].'__score'].' / '.count($obj['questions']), $this->derephrase($obj['end_message'])[$lang_id], '👉🏠'), $api_token);
                }
                else {
                    $telegram_message['message']=$this->send_multi_message_return_last_one($this->derephrase($obj['end_message'])[$lang_id].' 👉🏠', $api_token);
                }

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

    public function join_images(string $image1, string $image2, string $upload_dir): string
    {
        $dash = new Dash();

        // filename
        $output_file = "{$upload_dir}/" . time() . '.jpg';
        // upload target
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // iamgemagick command to stitch the images together
        $cmd = "convert -append {$image1} {$image2} {$output_file}";
        $dash->do_shell_command($cmd);

        return $output_file;
    }

    public function format_to_thousands(int $value): string
    {
        return number_format($value, 0, '.', ',');
    }

    public function get_form_map (array $bot): array
    {
        $dash = new Dash();

        $form_map = $dash->get_ids(['chatbot' => $bot['slug']], '=');
        $form_map = array_pop($form_map);

        return $dash->getObject($form_map['id']); // reduce form index for arrays but not for keys
    }

    public function get_registration_form (array $bot): array
    {
        $sql = new MySQL();
        $dash = new Dash();

        $module_and_form = $this->derephrase($bot['module_and_form_ids']);

        $registration_form = $sql->executeSQL("select * from data where type='form' and id={$module_and_form[0]} limit 1");
        $registration_form = $dash->doContentCleanup($registration_form);

        return array_pop($registration_form);
    }

    public function update_query_string($key, $value): string
    {
        $get_query = $_GET;
        $get_query[$key] = $value;
        $get_query = http_build_query($get_query);

        return str_replace(http_build_query($_GET), $get_query, $_SERVER['REQUEST_URI']);
    }
}
