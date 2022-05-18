<?php include_once __DIR__ . '/../_header.php'?>

<div class="container py-5">
	<h1 class="display-4 text-center mb-3"><span class="fal fa-analytics"></span>&nbsp;Insights Dashboard</h1>
<?php foreach ($dash->getObjects($dash->get_ids(array('type'=>'chatbot'), '=', 'AND')) as $chatbot) { ?>
	<a href="/report/chatbot?id=<?= $chatbot['id'] ?>&handle=<?= $chatbot['chatbot_handle'] ?>" target="new" class="w-100 d-block btn btn-primary my-2 btn-lg"><?= $chatbot['title'] ?></a>
<?php } ?>
	<a href="/admin" class="w-100 d-block btn btn-warning my-2 btn-lg">Admin Panel</a>
</div>

<?php include_once __DIR__ . '/../_footer.php'?>