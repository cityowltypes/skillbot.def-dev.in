<?php
include_once __DIR__ . '/_header.php';
\Wildfire\Core\Console::json($_SESSION);
?>

<div class="container py-5">
	<a href="/admin" class="w-100 d-block btn btn-warning my-2 btn-lg">Admin Panel</a>
<?php foreach ($dash->getObjects($dash->get_ids(array('type'=>'chatbot'), '=', 'AND')) as $chatbot) { ?>
	<a href="https://t.me/<?= $chatbot['chatbot_handle'] ?>" target="new" class="w-100 d-block btn btn-primary my-2 btn-lg"><?= $chatbot['title'] ?></a>
<?php } ?>
</div>

<?php include_once __DIR__ . '/_footer.php'?>
