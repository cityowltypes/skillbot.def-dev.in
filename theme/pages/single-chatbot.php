<?php
include_once __DIR__ . '/../_init.php';

//SEE IF ANY RESPONSE HAS BEEN RECEIVED FROM TELEGRAM
$telegram_response = (array) $api->body()['message'];

//GET KEY OF LAST MESSAGE SENT
$key_of_last_message_sent = $functions->get_last_message_sent($telegram_user_id, $slug);

//$dash->getAttribute( $dash->get_ids ( array( 'telegram_user_id'=>$telegram_user_id, 'chatbot'=>$slug ), 'AND', '=' )[0], 'identifier_of_last_message_sent' );

if ($telegram_response) {
	//LOG ALL RESPONSES
	$functions->log_response($telegram_response, $slug, 'telegram');
	$functions->save_response($telegram_response, $slug);
}

//IF THIS IS THE FIRST MESSAGE OR IF THE USER WANTS TO SWITCH LANGUAGE
//FORMAT CHATBOT LANGUAGES AS OPTIONS
$languages_available = $functions->format_languages_available($postdata);

//PREPARE THE TELEGRAM MESSAGE
$telegram_message = array();
$telegram_message['message'] = 'Choose language';
$telegram_message['response'] = $languages_available;

//SEND THE TELEGRAM MESSAGE
$functions->send_message($telegram_message, $postdata['api_token'], 'telegram');
?>