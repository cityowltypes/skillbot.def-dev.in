<?php
/**
 * @var object $dash;
 * @var object $functions;
 */
// include_once THEME_PATH . '/pages/_header.php';
include_once TRIBE_ROOT . '_init.php';
include_once __DIR__ . '/includes/functions.php';

use mikehaertl\pdftk\Pdf;
use \Wildfire\Core\Console as cc;
$functions = new \Wildfire\Theme\Functions();

$response = $dash->getObject($_GET['response_id']);
$chatbot = $dash->getObject($_GET['chatbot_id']);
$items = $functions->derephrase($chatbot['module_and_form_ids'], 1);
$registration_form_id = array_keys($items)[0];
$name_ques_id = trim($chatbot['certificate_user_name_question_number']) ?: 1;

$i = 0;
$incomplete = 0;

foreach ($items as $module_id=>$assessment_form_id) {
    if ($i && $module_id && !isset($response['completed__'.$module_id]))
      $incomplete = 1;
    $i++;
}

if ($response['id__'.$registration_form_id.'__'.$name_ques_id] && (!$incomplete || ($chatbot['allow_incomplete_certificate_download'] ?? false))) {
    $form_data = [
        'name' => $response["id__{$registration_form_id}__{$name_ques_id}"],
        'Name' => $response["id__{$registration_form_id}__{$name_ques_id}"],
        'NAME' => $response["id__{$registration_form_id}__{$name_ques_id}"]
    ];

    if (
      isset($chatbot['certificate_url']) &&
      trim($chatbot['certificate_url']) !== ''
    ) {
      $certificate_template = trim($chatbot['certificate_url']);
    }
    else {
      $certificate_template = THEME_PATH . '/docs/certificate.pdf';
    }

    $certificate_template = urldecode($certificate_template);
    $certificate_template = str_replace($_ENV['WEB_URL'], TRIBE_ROOT, $certificate_template);

    $pdf = new Pdf($certificate_template);
    $pdf->fillForm($form_data)->send("certificate.pdf");
}

die();
