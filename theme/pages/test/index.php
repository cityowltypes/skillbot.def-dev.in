<?php
require THEME_PATH . "/includes/functions.part.php";

$msg = new Messages();

// $debug = $msg->set_last_message_sent(1, 4, "4_63_57_0_64_5", "उपरोक्त सभी");
$key = $msg->get_last_message_sent(1, 4);
$key = $msg->get_next_message_key($key);
print_r($key);
