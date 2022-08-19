<?php

use \Wildfire\Core\Dash;
use \Wildfire\Core\MySQL;
use \Wildfire\Core\Console as cc;
use \Wildfire\Theme\Functions;


$dash = new Dash();
$sql = new MySQL();
$fn = new Functions();

// variables
$responses = null; // responses
$chatbot = null; // chatbot
$form_map = null; // mapping of form keys to form fields
$registration_form_id = null; // registration form id

/**
 * Chatbot
 */
$query = "SELECT * FROM `data` WHERE type = 'chatbot' AND id = {$_GET['id']} LIMIT 0,1";
$chatbot = $sql->executeSQL($query);

if (!$chatbot) {
    echo "No chatbot with this id found";
    die();
}

$chatbot = $dash->doContentCleanup($chatbot);
$chatbot = $chatbot[$_GET['id']];
$chatbot['module_and_form_ids'] = $fn->derephrase($chatbot['module_and_form_ids']);
$registration_form_id = $chatbot['module_and_form_ids'][0];
unset($chatbot['module_and_form_ids'][0]);

/**
 * Responses
 */
if ($_GET['sort']) {
    if ($_GET['sort'] === 'id') {
        $order = "id ";
    }
    else {
        $order = "content->>'$.id__{$_GET['sort']}' ";
    }

    $order .= strtolower($_GET['order'] ?? '') === 'desc' ? 'DESC' : 'ASC';

    $query = "SELECT * FROM `data` WHERE `type` = 'response' AND content->>'$.chatbot' = '{$chatbot['slug']}' order by $order LIMIT 0,50";
}
else {
    $query = "SELECT * FROM `data` WHERE `type` = 'response' AND content->>'$.chatbot' = '{$chatbot['slug']}' LIMIT 0,50";
}
$responses = $sql->executeSQL($query);

if (!$responses) {
    echo "No responses for this chatbot yet";
    die();
}

$responses = $dash->doContentCleanup($responses);

/**
 * Form Map
 */
$query = "SELECT * FROM `data` WHERE `type` = 'form_map' and content->>'$.chatbot' = '{$chatbot['slug']}' LIMIT 0,1";
$form_map = $sql->executeSQL($query);

if (!$form_map) {
    echo "Chatbot's form hasn't been mapped";
    die();
}

$form_map = $dash->doContentCleanup($form_map);
$form_map = array_pop($form_map);

require_once THEME_PATH . '/pages/_header.php';
?>

<div class="container pt-5">
    <h1><?= $chatbot['title'] ?></h1>
</div>

<div class="container mt-5">
    <table id="responses-table" class="table table-bordered">
        <thead>
            <tr>
                <?php
                $active_class = '';
                $order = '';
                $arrow = '';
                if ('id' === ($_GET['sort'] ?? null)) {
                    $active_class = 'active';
                    $arrow = strtolower($_GET['order'] ?? '') === 'desc' ? "<i class='fas fa-arrow-down'></i>" : "<i class='fas fa-arrow-up'></i>";
                    $order = strtolower($_GET['order'] ?? '') === 'desc' ? 'asc' : 'desc';
                }
                ?>
                <th class="cursor-pointer <?= $active_class ?>" data-sort="id" data-order="<?= $order ?>"># <?= $arrow ?></th>
                <?php
                $form_map_keys = ['age', 'state', 'district', 'gender', 'category'];

                foreach ($form_map_keys as $index => $key) {
                    if ($form_map[$key]) {
                        $sort_key = "{$registration_form_id}__{$form_map[$key]}";

                        $active_class = '';
                        $arrow = '';
                        $order = 'desc';

                        if ($sort_key === ($_GET['sort'] ?? null)) {
                            $active_class = 'active';
                            $arrow = strtolower($_GET['order'] ?? '') === 'desc' ? "<i class='fas fa-arrow-down'></i>" : "<i class='fas fa-arrow-up'></i>";

                            $order = strtolower($_GET['order'] ?? '') === 'desc' ? 'asc' : 'desc';
                        }

                        echo "<th class='text-capitalize text-center cursor-pointer $active_class' data-order='$order' data-sort='$sort_key'>$key $arrow</th>";
                    }
                    else {
                        // remove form_map_key value which hasn't been set
                        unset($form_map_keys[$index]);
                    }
                }
                ?>
            </tr>
        </thead>

        <tbody>
        <?php
        foreach ($responses as $response) {
            $td = "<th>{$response['id']}</th>";
            foreach ($form_map_keys as $key) {
                $form_key = "id__{$registration_form_id}__{$form_map[$key]}";

                $td .=  isset($response[$form_key]) ? "<td class='text-center' title='$key'>$response[$form_key]</td>" : '<td></td>';
            }

            echo "<tr>$td</tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<?php
require_once THEME_PATH . '/pages/_footer.php';
