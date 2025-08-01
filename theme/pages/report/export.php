<?php
/**
 * @var string $slug
 * @var object $sql
 * @var object $dash
 * @var object $functions
 */
set_time_limit(600);
use \Wildfire\Core\Console as cc;

include_once TRIBE_ROOT . '/theme/_init.php';

$dev = false;
if ($_GET['download'] ?? null === 'false') {
    $dev = true;
}

$filename="{$slug}-" . time();
$export = array();

$chatbot = $dash->getObject($_GET['id']);
$modules_and_forms_ids = $functions->derephrase($chatbot['module_and_form_ids']);
$modules = array_column($modules_and_forms_ids, 0);
$modules_and_forms_ids = $functions->derephrase($chatbot['module_and_form_ids'], 1);

$form_map = $functions->get_form_map($chatbot);
$user_form_id = $functions->get_registration_form($chatbot);
$user_form_id = $user_form_id['id'] ?? '';

$age_group = "content->>'$.id__{$user_form_id}__{$form_map['age']}' between 10 and 100";

if (trim($chatbot['min_age'] ?? '') && trim($chatbot['max_age'] ?? '')) {
    $age_group = "content->>'$.id__{$user_form_id}__{$form_map['age']}' between {$chatbot['min_age']} and {$chatbot['max_age']}";
}

if ($_GET['target'] === 'unfiltered') {
    $query = "SELECT *
    FROM `data`
    WHERE
          `type` = 'response' AND
          `content`->'$.chatbot' = '{$chatbot['slug']}'
    ORDER BY `id` DESC";
}
else {
    $query = "SELECT *
    FROM `data`
    WHERE
          `type` = 'response' AND
          `content`->'$.chatbot' = '{$chatbot['slug']}' AND
          {$age_group}
    ORDER BY `id` DESC";
}

$responses = $sql->executeSQL($query);

foreach ($responses as $key => $value) {
    $responses[$key] = json_decode($value['content'], 1);
    $responses[$key]['created_on'] = $value['created_on'];
}

$user_form = $dash->getObject($user_form_id);

$i = 0;
foreach ($responses as $response) {
	$export[$i]['response_id'] = $response['id'] ?? '';
	$export[$i]['created_on'] = date('d, M Y H:i', $response['created_on']);

	for ($j=1; $j <= sizeof($user_form['questions']) ; $j++) {
        $export[$i]["id__{$user_form_id}__{$j}"] = $response["id__{$user_form_id}__{$j}"] ?? "";
	}

    $incomplete = false;
    foreach ($modules as $_module_id) {
        if (
            isset($response["completed__$_module_id"]) &&
            $response["completed__$_module_id"] !== false
        ) {
            continue;
        }
        else {
            $incomplete = true;
        }
    }

    $export[$i]['certificate'] = $incomplete ? '❌' : '✅';

	$i++;
}

// generate csv file and make it available through download
$functions->array_to_csv($export, array_keys($export[0]), $filename);
