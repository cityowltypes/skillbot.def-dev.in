<?php
include_once __DIR__ . '/../_init.php';
$chatbot_slug = $postdata['slug'];
$chatbot_id = $postdata['id'];

//CHECK IF ANY RESPONSE HAS BEEN RECEIVED FROM TELEGRAM
$telegram_response = (array) $api->body()['message'];

//PROCEED ONLY IF TELEGRAM HAS CONNECTED
if ($telegram_response['from']['id'] ?? false) {

	//GET TELEGRAM USER ID
	$telegram_user_id = $telegram_response['from']['id'];

	//GET KEY OF LAST MESSAGE SENT
	$response_id = $functions->get_response_id($chatbot_slug, $telegram_user_id);

	//DELETE DATA MESSAGE (FOR TESTING)
	if ($response_id && strtolower(trim($telegram_response['text']))=='reset my chatbot data')
		$dash->doDeleteObject($response_id);

	//GET USER LANGUAGE
	$telegram_user_lang = $dash->getAttribute($response_id , 'lang');
	if ($telegram_user_lang=='##')
		$telegram_user_lang = 'en';

	//GET LAST SENT MESSAGE
	$last_message_identifier = '';
	if ($response_id)
		$last_message_identifier = $dash->getAttribute($response_id , 'last_message_identifier');

	//SAVE RESPONSE (CREATES RESPONSE ID WHEN CALLED FIRST TIME)
	$response_id = $functions->save_response($chatbot_slug, $telegram_user_id, $last_message_identifier, $telegram_response);

	//GET NEXT MESSAGE IDENTIFIER
	$next_message_identifier = $functions->get_next_message_identifier($chatbot_id, $last_message_identifier, $telegram_user_lang, $telegram_response['text'], $response_id);

	if ($next_message_identifier) {
		
		//PREPARE TELEGRAM MESSAGE AND RESPONSE OPTIONS
		$telegram_message = $functions->get_message($next_message_identifier, $chatbot_id, $telegram_user_lang);

		//SEND THE MESSAGE
		if ($telegram_message['message']) {
			$functions->send_message($telegram_message, $postdata['api_token'], 'telegram');

			//SET LAST MESSAGE SENT IDENTIFIER
			$last_message_identifier = $next_message_identifier;
			$functions->save_response($chatbot_slug, $telegram_user_id, $last_message_identifier);

			//SAVE LAST MESSAGE RESPONSE OPTIONS
			$dash->pushAttribute($response_id, 'last_message_response_options', json_encode($telegram_message['response']));
		}
	}
}
else {
	print_r($functions->get_message_array('id##4'));
}
?>