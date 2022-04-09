<?php

use \Wildfire\Theme\Functions;
use \Wildfire\Core\MySQL;
use \Wildfire\Core\Dash;
use \Wildfire\Core\Console;

class Messages extends Functions
{
    /**
     * Get key of last message
     * key = {chatbot_id}_{module_id}_{level_id}_{chapter_id}_{form_id}_{message_id}_{field_key};
     *
     * @param integer $user_id    user id of telegram user
     * @param integer $chatbot_id numeric id of chatbot
     *
     * @return void
     */
    public function get_last_message_sent(int $user_id, int $chatbot_id)
    {
        $sql = new MySQL;

        $data = $sql->executeSQL(
            "select * from data
            where type='response' and
                content->>'$.user_id'={$user_id} and
                content->>'$.chatbot_id'={$chatbot_id}
            orer by id desc
            limit 1"
        );
    }

    /**
     * Sets value for a particular key, when a message is sent
     *
     * @param integer     $user_id    user_id/telegram id of user
     * @param integer     $chatbot_id chatbot_id/slug
     * @param string      $key        key which will be used to identify messages
     * @param string|null $value      user's response for an instance of chat
     *
     * @return bool
     */
    public function set_last_message_sent(int $user_id, int $chatbot_id, string $key,
        string $value = null
    ) {
        $sql = new MySQL();
        $dash = new Dash();

        $res = $sql->executeSQL(
            "select * from data
            where type='response' and
            content->>'$.user_id'={$user_id} and
            content->>'$.chatbot_id'={$chatbot_id}
            limit 1"
        );

        // if $value is null then add key to responses with an empty string
        if ($res) {
            $res = $res[0];
            $content = json_decode($res['content'], 1);
            $content["responses"][$key] = !$value ? "" : $value;
        } else {
            // if response doesn't exist then create new one with lang set as empty
            $content = [
                'type' => 'response',
                'user_id' => $user_id,
                'chatbot_id' => $chatbot_id,
                'lang' => '',
                'content_privacy' => 'public'
            ];
        }

        $res_keys = $this->associate_key($key);

        // when field is "lang" and value & response exist in db
        if (($res_keys['field'] ?? null) == 'lang' && $res && $value) {
            $content['lang'] = $value;
        }

        if ($value) {
            // get form, and then the message using message_id
            $form = $dash->getObject($res_keys['form']);
            $messages = $form['questions'][$res_keys['message']];
            $messages = $this->derephrase($messages);

            foreach ($messages as $index => $message) {
                $message = implode("##", $message);

                if (strstr($message, $value)) {
                    break;
                }
            }

            $message = explode("##", $message);

            // $key here will be the key of $value in messages/questions
            foreach ($message as $index => $msg) {
                if ($msg == $value) {
                    break;
                }
            }

            // assuming that the first rephrase object is going to be the english one
            // this needs to be replaced with language detector
            $content['responses_en'][$key] = trim($messages[1][$index]);
        }

        if ($res) {
            $content = json_encode($content);
            $sql->executeSQL(
                "update data set content='{$content}' where id={$res['id']}
                order by id desc limit 1"
            );
        } else {
            $dash->pushObject($content);
        }

        return true;
    }
}
