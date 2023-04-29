<?php
/**
 * @var string $slug
 * @var object $sql
 * @var object $dash
 * @var object $functions
 */

use \Wildfire\Core\Console as cc;

include_once TRIBE_ROOT . '/theme/_init.php';

$dev = false;
if ($_GET['download'] ?? null === 'false') {
    $dev = true;
}

$filename="{$slug}-" . time();
$export = array();

$chatbot = $dash->getObject($_GET['id']);
$items = $functions->derephrase($chatbot['module_and_form_ids'], 1);

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

$i = 0;
foreach ($responses as $response) {
	$export[$i]['response_id'] = $response['id'] ?? '';
	$export[$i]['created_on'] = date('d, M Y H:i', $response['created_on']);

	for ($j=1; $j < 10 ; $j++) {
        $export[$i]["id__{$user_form_id}__{$j}"] = $response["id__{$user_form_id}__{$j}"] ?? "";
	}

    $items = $functions->derephrase($chatbot['module_and_form_ids'], 1);

    $k = 0;
    $incomplete = false;
    foreach ($items as $module_id => $assessment_form_id) {
        if ($k && $module_id && !isset($response["completed__$module_id"])) {
            $incomplete = true;
            break;
        }
        $k++;
    }

    $export[$i]['certificate'] = $incomplete ? '❌' : '✅';

	$i++;
}

// generate csv file and make it available through download
$functions->array_to_csv($export, array_keys($export[0]), $filename);
