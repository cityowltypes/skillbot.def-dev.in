<?php
use \Wildfire\Core\Dash;
use \Wildfire\Core\MySQL;
use \Wildfire\Theme\Functions;

include_once __DIR__ . '/../_header.php';
require_once 'includes/_nav.php';

$dash = new Dash;

if (($_SESSION['role_slug'] ?? null) !== 'admin') {
    header('Location: /user/login');
    die();
}

$sql = new MySQL;
$fn = new Functions();

$stats = array_merge(
    $sql->executeSQL("select count(*) as 'response' from data where type = 'response'")[0],
    $sql->executeSQL("select count(*) as 'form' from data where type = 'form'")[0],
    $sql->executeSQL("select count(*) as 'module' from data where type = 'module'")[0],
    $sql->executeSQL("select count(*) as 'chapter' from data where type = 'chapter'")[0],
    $sql->executeSQL("select count(*) as 'level' from data where type = 'level'")[0],
    $sql->executeSQL("select count(*) as 'chatbot' from data where type = 'chatbot'")[0]
);
?>

<div class="main">
    <div id="dash-viewport" class="container py-5">
        <div class="text-center">
            <img src="/theme/assets/img/def_logo.png" class="brand-logo" alt="">
        </div>

        <p class="display-2 border-bottom mb-3">
            Chatbots
        </p>

        <?php foreach ($dash->getObjects($dash->get_ids(array('type'=>'chatbot'), '=', 'AND')) as $chatbot) : ?>
        <a
            href="/report/chatbot?id=<?= $chatbot['id'] ?>&handle=<?= $chatbot['chatbot_handle'] ?>"
            class="btn btn-primary-custom my-2 btn-lg">
            <?= $chatbot['title'] ?>
        </a>
        <?php endforeach ?>

        <div class="col-lg-6 mt-5">
            <h2 class="fw-light mb-4">Overall Stats</h2>

            <table class="table table-bordered table-hover">
                <thead class="table-dark">
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Category</th>
                    <th scope="col">Stats</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $i = 0;
                foreach($stats as $key => $value) {
                    $i++;
                    $value = $fn->format_to_thousands($value);
                    $key = ucwords($key);

                    echo "<tr>
                            <th scope='row'>{$i}</th>
                            <td>{$key}</td>
                            <td>{$value}</td>
                        </tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../_footer.php'?>