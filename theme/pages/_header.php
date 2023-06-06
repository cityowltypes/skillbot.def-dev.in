<?php include_once __DIR__ . '/../_init.php';?>
<!doctype html>
<html lang="<?=$types['webapp']['lang'] ?? 'en'?>">
<head>
	<?php $starttime = microtime(true); ?>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

	<title><?=$meta_title ?? ''?></title>
	<meta name="description" content="<?=$meta_description ?? ''?>">
	<meta property="og:title" content="<?=$meta_title?>">
	<meta property="og:description" content="<?=$meta_description?>">
	<meta property="og:image" content="<?=$meta_image_url ?? ''?>">

	<link href="/node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://use.typekit.net/jjw4adm.css">

	<link href="/vendor/wildfire/admin/theme/assets/plugins/fontawesome/css/all.min.css" rel="stylesheet">
	<link href="/theme/assets/css/custom.css" rel="stylesheet">
</head>

<body>
