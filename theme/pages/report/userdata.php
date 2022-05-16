<?php include_once TRIBE_ROOT . '/theme/includes/_init.php';?>

<?php
$filename=$slug.'-' . time();

$data1 = $sql->executeSQL("SELECT 
	`d1`.`id` `id`,
	`d1`.`type` `type`,
	FROM `data` `d1`
	WHERE 
	`d1`.`type`='user'
	ORDER BY `id` DESC");


//$data=array_combine(array_column($data2, 'id'), $data2) + array_combine(array_column($data1, 'id'), $data1);

$data = array_combine(array_column($data1, 'id'), $data1);

$functions->array_to_csv($data, array_keys($data1[0]), $filename);