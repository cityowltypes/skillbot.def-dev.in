<?php
use \Wildfire\Core\Dash;
use \Wildfire\Core\MySQL;
use \Wildfire\Theme\Functions;

require_once __DIR__ . '/../_header.php';
require_once 'includes/_nav.php';

$dash = new Dash;

if (($_SESSION['role_slug'] ?? null) !== 'admin') {
    header('Location: /user/login');
    die();
}

$sql = new MySQL;
$fn = new Functions();

$stats = $sql->executeSQL("select count(*) as `count`, `type` from data where type in ('response', 'form', 'module', 'chapter', 'level', 'chatbot') group by type order by count desc");

$traffic_stat = $sql->executeSQL(
    "select
    date(from_unixtime(created_on)) as creation_date,
    count(*) as 'count'
from data
where type = 'response' and date(from_unixtime(created_on)) >= ( CURDATE() - INTERVAL 14 DAY )
group by creation_date"
);

unset($temp);

$temp['date'] = array_column($traffic_stat, 'creation_date');
$temp['count'] = array_column($traffic_stat, 'count');
$traffic_stat = json_encode($temp);

echo "<script>
const TRAFFIC = {$traffic_stat};
</script>";
?>

<div class="main">
    <div id="dash-viewport" class="container py-5">
        <div class="text-center">
            <img src="/theme/assets/img/def_logo.png" class="brand-logo" alt="">
        </div>

        <p class="display-2 border-bottom mb-3">
            Chatbots
        </p>

        <?php foreach ($dash->getObjects($dash->get_ids(['type' => 'chatbot'], '=', 'AND')) as $chatbot) : ?>
        <a
            href="/report/chatbot?id=<?= $chatbot['id'] ?>&handle=<?= $chatbot['chatbot_handle'] ?>"
            class="btn btn-primary-custom my-2 btn-lg">
            <?= $chatbot['title'] ?>
        </a>
        <?php endforeach ?>

        <div class="row">
            <h2 class="fw-light mt-5 mb-4">Overall Stats</h2>

            <div class="col-lg-6">
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
                    foreach ($stats as $value) {
                        $i++;
                        $value['count'] = $fn->format_to_thousands($value['count']);
                        $value['type'] = ucwords($value['type']);

                        echo "<tr>
                            <th scope='row'>{$i}</th>
                            <td>{$value['type']}s</td>
                            <td>{$value['count']}</td>
                        </tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div
                        class="card-header small fw-bold
                        text-uppercase bg-primary-custom text-light">
                        Responses (last 14 days)
                    </div>

                    <div class="card-body">
                        <canvas id="responses_by_date" width="400" height="400">
                        </canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../_footer.php'?>
