<?php
/**
 * @var string $slug
 * @var object $sql
 * @var object $dash
 * @var object $functions
 */

include_once TRIBE_ROOT . '/theme/_init.php';
$filename=$slug.'-' . time();
$export = array();

$chatbot = $dash->getObject($_GET['id']);
$items = $functions->derephrase($chatbot['module_and_form_ids'], 1);

$age_group = "content->>'$.id__5__6' between 10 and 100";

if (trim($chatbot['min_age'] ?? '') && trim($chatbot['max_age'] ?? '')) {
    $age_group = "content->>'$.id__5__6' between {$chatbot['min_age']} and {$chatbot['max_age']}";
}

$ids = $sql->executeSQL("SELECT `id` FROM `data` 
    WHERE `content_privacy`='private' AND 
          `content`->'$.type' = 'response' AND 
          `content`->'$.chatbot' = '{$chatbot['slug']}' AND
          {$age_group}
    ORDER BY `id` DESC");

$data = $dash->getObjects($ids);
$ids = array_column($data, 'id');
array_multisort($ids, SORT_DESC, $data);

$i = 0;
foreach ($data as $row) {
	foreach ($items as $key => $form_id) {
		if ($form_id) {
			$export[$i]['response_id'] = $row['id'];
			for ($j=1; $j < 10 ; $j++) { 
				$export[$i]['id__'.$form_id.'__'.$j]=$row['id__'.$form_id.'__'.$j];
			}
			$i++;
		}
	}
}

$functions->array_to_csv($export, array_keys($export[0]), $filename);