<?php
include_once __DIR__ . '/../_init.php';
$chatbot_slug = $postdata['slug'];
$chatbot_id = $postdata['id'];

//CHECK IF ANY RESPONSE HAS BEEN RECEIVED FROM TELEGRAM
$telegram_response = (array) $api->body()['message'];

//GET TELEGRAM USER ID
if ($telegram_user_id = $telegram_response['from']['id'] ?? false) {

	//GET KEY OF LAST MESSAGE SENT
	$response_id = $sql->executeSQL("SELECT `id` FROM `data` WHERE `content_privacy`='private' AND `content`->'$.telegram_user_id' = ".$telegram_user_id." AND `content`->'$.chatbot' = '".$chatbot_slug."' AND `content`->'$.type' = 'response'")[0]['id'];

	//DELETE DATA MESSAGE (FOR TESTING)
	if ($response_id && strtolower(trim($telegram_response['text']))=='chatbot_reset') {
		$dash->doDeleteObject($response_id);
		$response_id = false;
	}

	//GET MY CHATBOT ID
	else if ($response_id && strtolower(trim($telegram_response['text']))=='chatbot_uid') {
		$telegram_message['message']='000';
		$telegram_message['response']['id##'.$chatbot_id] = '🏠';
		$next_message_identifier = 'chatbot##uid';
	}

	else if ($response_id) {
		
		//GET LAST SENT MESSAGE
		$last_message_identifier = $dash->getAttribute($response_id , 'last_message_identifier');

		
		//NEXT MESSAGE IDENTIFIER
		if ($last_message_identifier == 'lang##'.$chatbot_id) {
			$dash->pushAttribute($response_id, 'lang', $telegram_response['text']);
			//GET USER LANGUAGE
			$telegram_user_lang = $dash->getAttribute($response_id , 'lang');
			$next_message_identifier = 'id##'.$chatbot_id;
		}
		else {
			//GET USER LANGUAGE
			$telegram_user_lang = $dash->getAttribute($response_id , 'lang');
			$last_message_response_options = json_decode($dash->getAttribute($response_id, 'last_message_response_options'), true);
        	$next_message_identifier = array_search($telegram_response['text'], $last_message_response_options);
        	
        	if (count($tring = $functions->derephrase($last_message_identifier))>3) {
        		$str_tring = $tring[0].'##'.$tring[1].'##'.$tring[2];
        		$dash->pushAttribute($response_id, implode('__', $functions->derephrase($str_tring)), $telegram_response['text']);
        	}
        	else
	        	$dash->pushAttribute($response_id, implode('__', $functions->derephrase($last_message_identifier)), $telegram_response['text']);

	        $form_score_name = 'id__'.$tring[1].'__score';
    		$last_question_correct_response = $dash->getAttribute($response_id , 'last_question_correct_response');
    		if ($last_question_correct_response == $telegram_response['text']) {
    			$form_score = (int) $dash->getAttribute($response_id , $form_score_name) + 1;
    			$dash->pushAttribute($response_id, $form_score_name, $form_score);
    		}

        	$this_id_type = $dash->getAttribute($tring[1], 'type');
        	if ($this_id_type == 'form') {
	        	if ($telegram_response['text'] == '👉👉👉' && $tring[2]) {
	        		$next_message_identifier = $last_message_identifier;
	        	} else if (trim($telegram_response['text']) && $telegram_response['text']!='🏠') {
	        		$next_message_identifier = $tring[0].'##'.$tring[1].'##'.((int)$tring[2]+1);
	        	}
	        }

		}

	}
	else  {
		$next_message_identifier = 'lang##'.$chatbot_id;
		$telegram_user_lang = '';
		
		//CREATES RESPONSE ID WHEN CALLED FIRST TIME
        $obj = array();
        $obj['title'] = $chatbot_slug.' '.$telegram_user_id;
        $obj['type']='response';
        $obj['content_privacy']='private';
        $obj['chatbot']=$chatbot_slug;
        $obj['telegram_user_id']=$telegram_user_id;
        $response_id = $dash->pushObject($obj);
	}

	if ($next_message_identifier) {
		
		//PREPARE TELEGRAM MESSAGE AND RESPONSE OPTIONS
		$telegram_message = $functions->get_message_array($next_message_identifier, $chatbot_id, $telegram_user_lang, $response_id, $postdata['api_token']);

		//SEND THE MESSAGE
		if ($telegram_message['message'] ?? false) {
			$functions->send_message($telegram_message, $postdata['api_token']);

			//SET NEW MESSAGE IDENTIFIER, for 'last_message_identifier'
			$dash->pushAttribute($response_id, 'last_message_identifier', $next_message_identifier);
			$dash->pushAttribute($response_id, $next_message_identifier, '##');
			//SAVE LAST MESSAGE RESPONSE OPTIONS
			$dash->pushAttribute($response_id, 'last_message_response_options', json_encode($telegram_message['response']));
		}
	}
}
else {
	print_r($functions->derephrase(json_decode($dash->getAttribute(5, 'questions'), true)[1], 1, [], 1)['arr']);
}
?>