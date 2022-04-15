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

    public static function gst_states() {
        $gst_state = array();
        $gst_state['35'] = 'Andaman and Nicobar Islands';
        $gst_state['37'] = 'Andhra Pradesh';
        $gst_state['12'] = 'Arunachal Pradesh';
        $gst_state['18'] = 'Assam';
        $gst_state['10'] = 'Bihar';
        $gst_state['4'] = 'Chandigarh';
        $gst_state['22'] = 'Chattisgarh';
        $gst_state['26'] = 'Dadra & Nagar Haveli and Daman & Diu';
        $gst_state['7'] = 'Delhi';
        $gst_state['30'] = 'Goa';
        $gst_state['24'] = 'Gujarat';
        $gst_state['6'] = 'Haryana';
        $gst_state['2'] = 'Himachal Pradesh';
        $gst_state['1'] = 'Jammu and Kashmir';
        $gst_state['20'] = 'Jharkhand';
        $gst_state['29'] = 'Karnataka';
        $gst_state['32'] = 'Kerala';
        $gst_state['38'] = 'Ladakh';
        $gst_state['31'] = 'Lakshadweep Islands';
        $gst_state['23'] = 'Madhya Pradesh';
        $gst_state['27'] = 'Maharashtra';
        $gst_state['14'] = 'Manipur';
        $gst_state['17'] = 'Meghalaya';
        $gst_state['15'] = 'Mizoram';
        $gst_state['13'] = 'Nagaland';
        $gst_state['21'] = 'Odisha';
        $gst_state['97'] = 'Other Territory';
        $gst_state['34'] = 'Pondicherry';
        $gst_state['3'] = 'Punjab';
        $gst_state['8'] = 'Rajasthan';
        $gst_state['11'] = 'Sikkim';
        $gst_state['33'] = 'Tamil Nadu';
        $gst_state['36'] = 'Telangana';
        $gst_state['16'] = 'Tripura';
        $gst_state['9'] = 'Uttar Pradesh';
        $gst_state['5'] = 'Uttarakhand';
        $gst_state['19'] = 'West Bengal';
        return $gst_state;
    }

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


    public function get_video_embed_url($url) {
        if ($vid = $this->get_youtube_id($url)) {
            return 'https://www.youtube.com/embed/' . $vid . '?autoplay=1&cc_load_policy=1';
        } else if ($vid = $this->get_vimeo_id($url)) {
            return 'https://player.vimeo.com/video/' . $vid . '?autoplay=1&cc_load_policy=1';
        } else {
            return 0;
        }

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

    public function get_vimeo_id($link) {

        $regexstr = '~
            # Match Vimeo link and embed code
            (?:<iframe [^>]*src=")?     # If iframe match up to first quote of src
            (?:                         # Group vimeo url
                https?:\/\/             # Either http or https
                (?:[\w]+\.)*            # Optional subdomains
                vimeo\.com              # Match vimeo.com
                (?:[\/\w]*\/videos?)?   # Optional video sub directory this handles groups links also
                \/                      # Slash before Id
                ([0-9]+)                # $1: VIDEO_ID is numeric
                [^\s]*                  # Not a space
            )                           # End group
            "?                          # Match end quote if part of src
            (?:[^>]*></iframe>)?        # Match the end of the iframe
            (?:<p>.*</p>)?              # Match any title information stuff
            ~ix';

        preg_match($regexstr, $link, $matches);

        return $matches[1];

    }

    public function rephrase ($rephrase_string){
        if (strstr($rephrase_string, '##')) {
            $rephrased_text = explode('##', $rephrase_string);
            foreach ($rephrased_text as $temp_val) {
                $rephrased_subtext = explode('::', $temp_val);
                print_r($rephrased_subtext);
            }
        }
        return;
    }
    public function derephrase ($str){
        $a = explode('##',$str);
        unset($a[0]);
        //print_r($a);
        $arr = array();
        foreach($a as $key=>$i){
            $m = explode('::', $i);
            $n = $m[0];
            $arr[$key] = $m;
        }
        //print_r($arr);
        return $arr;

    }

    /**
     * Function to expand and create associative array of the response key
     *
     * @param string $key it gives info of exact parent of response
     *
     * @return array
     */
    public function associate_key(string $key)
    {
        $arr = explode('_', $key);
        $as_key = array();
        $key_index_names = ['chatbot', 'module', 'level', 'chapter', 'form',
        'message', 'field'];

        foreach ($arr as $key => $value) {
            $as_key[$key_index_names[$key]] = $value;
        }

        return $as_key;
    }

    public function log_response($response, $chatbot_slug, $channel='telegram') {
        $dash = new Dash;

        $obj = array();

        if ($channel=='telegram') {
            $obj['title'] = $chatbot_slug.' '.$response['from']['id'];
            $obj['type']='response_log';
            $obj['content_privacy']='private';
            $obj['response']=$response;
            $obj['chatbot']=$chatbot_slug;
            $obj['telegram_user_id']=$response['from']['id'];
        }

        return $dash->pushObject($obj);

    }

    public function send_message ( 
                $message, 
                $api_token='', 
                $channel='telegram' ) {

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

            return $botman->listen();
        }

        else {
            return false;
        }
    }


    public function format_languages_available($postdata) {

        $language_array = array_values(
                            array_filter ( 
                                array_map( 'trim', 
                                    explode('##', $postdata['languages']))
                            )
                        );

        foreach ($language_array as $language) {
                $language_temp = array_values(
                        array_filter ( 
                            array_map( 'trim', 
                                explode('::', $language))
                        )
                    );
                $languages_available[$language_temp[0]] = $language_temp[1];
        }

        return array_map('ucfirst', array_map('strtolower', array_values($languages_available)));
    }
}