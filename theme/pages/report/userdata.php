<?php include_once TRIBE_ROOT . '/theme/_init.php';
$filename=$slug.'-' . time();
$export = array();
$ids = $sql->executeSQL("SELECT `id` FROM `data` WHERE `content_privacy`='private' AND `content`->'$.type' = 'response' AND `content`->'$.chatbot' = 'digital-financial-inclusion' ORDER BY `id` DESC");
$data = $dash->getObjects($ids);
$ids = array_column($data, 'id');
array_multisort($ids, SORT_DESC, $data);
$i = 0;
foreach ($data as $row) {
	for ($j=1; $j < 10 ; $j++) { 
		$export[$i]['id__5__'.$j]=$row['id__5__'.$j];
	}
	$i++;
}
$functions->array_to_csv($export, array_keys($export[0]), $filename);