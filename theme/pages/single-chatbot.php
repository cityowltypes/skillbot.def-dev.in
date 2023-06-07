<?php
/**
 * @var object $api
 * @var object $sql
 * @var object $dash
 * @var array $postdata
 */
include_once __DIR__ . '/../_init.php';
$chatbot_slug = $postdata['slug'];
$chatbot_id = $postdata['id'];

$emojis = array();
$emojis['done'] = $postdata['emoji_done'] ?? '✅';
$emojis['next'] = $postdata['emoji_next'] ?? '👉';
$emojis['home'] = $postdata['emoji_home'] ?? '🏠';
$emojis['youwerehere'] = $postdata['emoji_youwerehere'] ?? '➡️';

//CHECK IF ANY RESPONSE HAS BEEN RECEIVED FROM TELEGRAM
$telegram_response = (array) $api->body()['message'];

//GET TELEGRAM USER ID
if ($telegram_user_id = $telegram_response['from']['id'] ?? false) {
	
	//GET KEY OF LAST MESSAGE SENT
	$response_id = $sql->executeSQL("SELECT `id` FROM `data` WHERE `content_privacy`='private' AND `content`->'$.telegram_user_id' = ".$telegram_user_id." AND `content`->'$.chatbot' = '".$chatbot_slug."' AND `content`->'$.type' = 'response'")[0]['id'];
	$main_response_id = $response_id;

	$current_user = ($dash->getAttribute((int) $main_response_id , 'current_user') ?? '0');
	if (((int) $current_user) >= 2) {
		$telegram_user_id = $telegram_user_id.'-'.$current_user;
		$response_id = $sql->executeSQL("SELECT `id` FROM `data` WHERE `content_privacy`='private' AND `content`->'$.telegram_user_id' = '".$telegram_user_id."' AND `content`->'$.chatbot' = '".$chatbot_slug."' AND `content`->'$.type' = 'response'")[0]['id'];
	}

	//DELETE DATA MESSAGE (FOR TESTING)
	if ($response_id && strtolower(trim($telegram_response['text']))=='chatbot_reset') {
		$dash->doDeleteObject($response_id);
		$response_id = false;
	}

	//GET MY CHATBOT ID
	else if ($response_id && strtolower(trim($telegram_response['text']))=='chatbot_uid') {
		$telegram_message['message'] = $telegram_user_id.' ~ '.$response_id;
		$telegram_message['response']['id##'.$chatbot_id] = $emojis['home'];
		$next_message_identifier = 'chatbot##uid';
	}

	else if ($response_id) {
		//GET LAST SENT MESSAGE
		$last_message_identifier = $dash->getAttribute((int) $response_id , 'last_message_identifier');
		
		//NEXT MESSAGE IDENTIFIER
		if ($last_message_identifier == 'lang##'.$chatbot_id) {
			$dash->pushAttribute($response_id, 'lang', $telegram_response['text']);
			//GET USER LANGUAGE
			$telegram_user_lang = $dash->getAttribute((int) $response_id , 'lang');
			$next_message_identifier = 'id##'.$chatbot_id;
		}
		else if ($last_message_identifier == 'cert##'.$chatbot_id) {
			$dash->pushAttribute($response_id, 'cert', $telegram_response['text']);
			$next_message_identifier = 'id##'.$chatbot_id;
		}
		else if ($last_message_identifier == 'confirmReset##'.$chatbot_id) {
			$dash->doDeleteObject($response_id);
			$response_id = false;
			$next_message_identifier = 'id##'.$chatbot_id;
		}
		else {
			//GET USER LANGUAGE
			$telegram_user_lang = $dash->getAttribute((int) $response_id , 'lang');
			$last_message_response_options = json_decode($dash->getAttribute((int) $response_id, 'last_message_response_options'), true) ?? [];
			$next_message_identifier = array_search($telegram_response['text'], ($last_message_response_options ?? []));
        	
        	if (substr($next_message_identifier, 0, 12) == 'switchuser##') {
				$dash->pushAttribute($main_response_id, implode('__', $functions->derephrase($next_message_identifier)), $telegram_response['text']);
	        	$current_user = $functions->derephrase($next_message_identifier)[2];
	        	if ($current_user == 'new') {
	                $multiuser_count = $dash->getAttribute((int) $main_response_id, 'multiuser_count');
	                if ($multiuser_count ?? false)
	                    $multiuser_count = (int) $multiuser_count + 1;
	                else
	                    $multiuser_count = 2;
	                $dash->pushAttribute($main_response_id, 'multiuser_count', $multiuser_count);
	                $current_user = $multiuser_count;
	            }

	        	$dash->pushAttribute($main_response_id, 'current_user', $current_user);
				$next_message_identifier = 'switchuser##'.$chatbot_id.'##'.$current_user;
        	}
        	else if (count($tring = $functions->derephrase($last_message_identifier))>3) {
        		$str_tring = $tring[0].'##'.$tring[1].'##'.$tring[2];
        		$dash->pushAttribute($response_id, implode('__', $functions->derephrase($str_tring)), $telegram_response['text']);
        	}
        	else
	        	$dash->pushAttribute($response_id, implode('__', $functions->derephrase($last_message_identifier)), $telegram_response['text']);

	        $form_score_name = 'id__'.$tring[1].'__score';
    		$last_question_correct_response = $dash->getAttribute((int) $response_id , 'last_question_correct_response');
    		if ($last_question_correct_response == $telegram_response['text']) {
    			$form_score = (int) $dash->getAttribute((int) $response_id , $form_score_name) + 1;
    			$dash->pushAttribute($response_id, $form_score_name, $form_score);
    		}

    		if (is_int($tring[1]) || is_numeric($tring[1]))
	        	$this_id_type = $dash->getAttribute((int) $tring[1], 'type');
        	
        	if ($this_id_type == 'form') {
	        	$last_message_identifier_response_type_array = $functions->derephrase($last_message_identifier);
	        	$last_message_identifier_response_type = $functions->derephrase(json_decode($dash->getAttribute((int) $last_message_identifier_response_type_array[1], 'questions'), true)[($last_message_identifier_response_type_array[2] - 1)])[1][1];
	        	
        		if ($last_message_identifier_response_type == 'mobile' && strlen($telegram_response['text'])!=10) {
	        		$next_message_identifier = $last_message_identifier;
	        	}
	        	else if (($telegram_response['text'] == ($emojis['next'].$emojis['next'].$emojis['next'])) && $tring[2]) {
	        		$next_message_identifier = $last_message_identifier;
	        	}
	        	else if (array_values($last_message_response_options)[0] != ($emojis['next'].$emojis['next'].$emojis['next']) && !in_array($telegram_response['text'], array_values($last_message_response_options))) {
					$next_message_identifier = $last_message_identifier;
	        	}
	        	else if (trim($telegram_response['text']) && $telegram_response['text']!=$emojis['home']) {
	        		$next_message_identifier = $tring[0].'##'.$tring[1].'##'.((int)$tring[2]+1);
	        	}
	        }

		}

	}
	
	if (!$response_id)  {
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
		if ($next_message_identifier != 'chatbot##uid') {
			$telegram_message = $functions->get_message_array($next_message_identifier, (int) $chatbot_id, $telegram_user_lang, (int) $response_id, $postdata['api_token'], (int) $main_response_id);
		}

		//SEND THE MESSAGE
		if ($telegram_message['message'] ?? false) {
			$functions->send_message($telegram_message, $postdata['api_token']);

			//SET NEW MESSAGE IDENTIFIER, for 'last_message_identifier'
			$dash->pushAttribute($response_id, 'last_message_identifier', $next_message_identifier);
			$dash->pushAttribute($response_id, implode('__', $functions->derephrase($next_message_identifier)), '##');
			//SAVE LAST MESSAGE RESPONSE OPTIONS
			$dash->pushAttribute($response_id, 'last_message_response_options', json_encode($telegram_message['response']));
		}
	}
}
else {
	//print_r($functions->derephrase(json_decode($dash->getAttribute(5, 'questions'), true)[1], 1, [], 1)['arr']);
}
?>
