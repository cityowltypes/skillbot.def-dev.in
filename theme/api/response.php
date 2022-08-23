<?php
require_once THEME_PATH . '/includes/functions.php';

use \Wildfire\Api;
use \Wildfire\Core\Dash;
use \Wildfire\Core\MySQL;
use \Wildfire\Theme\Functions;

$api = new Api();
$dash = new Dash();
$fn = new Functions;

$valid_indices = ['age', 'name', 'state', 'district', 'gender', 'category'];

// api is accessible only to admins
if ($_SESSION['role'] !== 'admin') {
    $api->send(401);
}

if ($api->method('get')) {
    if (
    !(
        isset($_GET['id']) &&
        is_numeric($_GET['id'])
    )
    ) {
        $api->json(['ok' => false, 'error' => 'valid `id` is required'])->send(400);
    }

    if (
    !(
        isset($_GET['chatbot']) &&
        is_numeric($_GET['chatbot'])
    )
    ) {
        $api->json(['ok' => false, 'error' => 'valid `chatbot` is required'])->send(400);
    }

    $response = $dash->getObject($_GET['id']);
    if (!$response) {
        $api->json(['ok' => true, 'error' => 'response not found'])->send(404);
    }

    $chatbot = $dash->getObject($_GET['chatbot']);
    if (!$chatbot) {
        $api->json(['ok' => false, 'error' => 'chatbot not found'])->send(404);
    }

    $form_map = $dash->get_ids(['chatbot' => $chatbot['slug'], 'type' => 'form_map'], '=', '&&', 'id', 'desc', '1');
    $form_map = $dash->getObjects($form_map);
    if ($form_map) {
        $form_map = array_pop($form_map);
    }

    $registration_form_id = $chatbot['module_and_form_ids'];
    $registration_form_id = $fn->derephrase($registration_form_id)[0];

    $res['id'] = $response['id'];
    foreach ($valid_indices as $index) {
        $key = "id__{$registration_form_id}__{$form_map[$index]}";

        if (isset($response[$key])) {
            $res[$index] = $response[$key];
        }
    }

    $api->json($res)->send();
}

if ($api->method('post')) {
    $response = $dash->getObject($_POST['id']);
    if (!$response) {
        $api->json(['ok' => true, 'error' => 'response not found'])->send(404);
    }

    $chatbot = $dash->getObject($_POST['chatbot_id']);
    if (!$chatbot) {
        $api->json(['ok' => false, 'error' => 'chatbot not found'])->send(404);
    }

    $form_map = $dash->get_ids(['chatbot' => $chatbot['slug'], 'type' => 'form_map'], '=', '&&', 'id', 'desc', '1');
    $form_map = $dash->getObjects($form_map);
    if ($form_map) {
        $form_map = array_pop($form_map);
    }

    $registration_form_id = $chatbot['module_and_form_ids'];
    $registration_form_id = $fn->derephrase($registration_form_id)[0];

    foreach ($valid_indices as $index) {
        $key = "id__{$registration_form_id}__{$form_map[$index]}";

        if (isset($_POST[$index])) {
            $dash->pushAttribute($_POST['id'], $key, $_POST[$index]);
        }
    }

    $api->json($_POST)->send('200');
}
