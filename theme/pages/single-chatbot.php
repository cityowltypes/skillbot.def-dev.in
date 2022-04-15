<?php
include_once __DIR__ . '/../_init.php';

//SEE IF ANY RESPONSE HAS BEEN RECEIVED FROM TELEGRAM
$telegram_response = (array) $api->body()['message'];

if ($telegram_response) {
	//LOG ALL RESPONSES
	$functions->log_response($telegram_response, $slug, 'telegram');
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