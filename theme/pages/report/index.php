<?php
use \Wildfire\Core\Dash;

include_once __DIR__ . '/../_header.php';
require_once 'includes/_nav.php';

$dash = new Dash;

if (($_SESSION['role_slug'] ?? null) !== 'admin') {
    header('Location: /user/login');
    die();
}
?>

<div class="main">
    <div id="dash-viewport" class="container py-5">
        <div class="text-center"><img src="/theme/assets/img/def_logo.png" class="brand-logo" alt=""></div>
        <p class="display-2 border-bottom mb-3">Chatbots</p>
        <?php foreach ($dash->getObjects($dash->get_ids(array('type'=>'chatbot'), '=', 'AND')) as $chatbot) : ?>
        <a
            href="/report/chatbot?id=<?= $chatbot['id'] ?>&handle=<?= $chatbot['chatbot_handle'] ?>"
            class="btn btn-primary-custom my-2 btn-lg">
            <?= $chatbot['title'] ?>
        </a>
        <?php endforeach ?>

    </div>
</div>

<?php include_once __DIR__ . '/../_footer.php'?>