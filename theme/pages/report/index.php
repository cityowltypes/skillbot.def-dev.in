<?php
include_once __DIR__ . '/../_header.php';
require_once 'includes/_nav.php';
?>

<div class="main">
    <div id="dash-viewport" class="container py-5">
        <p class="display-2 border-bottom mb-3">Chatbots</p>
        <?php foreach ($dash->getObjects($dash->get_ids(array('type'=>'chatbot'), '=', 'AND')) as $chatbot) : ?>
        <a
            href="/report/chatbot?id=<?= $chatbot['id'] ?>&handle=<?= $chatbot['chatbot_handle'] ?>"
            target="_blank"
            class="btn btn-primary my-2 btn-lg">
            <?= $chatbot['title'] ?>
        </a>
        <?php endforeach ?>

    </div>
</div>

<?php include_once __DIR__ . '/../_footer.php'?>