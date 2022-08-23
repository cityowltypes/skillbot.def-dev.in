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
$page = $_GET['page'] ?? 1;
$upper_limit = 50;
$limit = ($page - 1) * $upper_limit;
$pages_count = null; // pages to be calculated for responses listing
$search_query = urldecode($_GET['search']) ?? null;
$search = '';
$query = null;
$query_limit = "LIMIT $limit,$upper_limit";
$form_map_keys = ['name', 'age', 'state', 'district', 'gender', 'category'];

/**
 * Chatbot
 */
$chatbot = $sql->executeSQL("SELECT * FROM `data` WHERE type = 'chatbot' AND id = {$_GET['id']} LIMIT 0,1");

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
 * Form Map
 */
$form_map = $sql->executeSQL("SELECT * FROM `data` WHERE `type` = 'form_map' and content->>'$.chatbot' = '{$chatbot['slug']}' LIMIT 0,1");

if (!$form_map) {
    echo "Chatbot's form hasn't been mapped";
    die();
}

$form_map = $dash->doContentCleanup($form_map);
$form_map = array_pop($form_map);

foreach ($form_map_keys as $index => $key) {
    if (!$form_map[$key]) {
        unset($form_map_keys[$index]);
    }
}

/**
 * Responses
 */
if (isset($_GET['export'])) {
    $query_limit = '';
}

if (!$query && $search_query) {
    $rephrase = strpos($search_query, '##');
    if ($rephrase !== false) {
        $rephrase = $fn->derephrase($search_query);
        $search = [];
        foreach ($rephrase as $item) {
            if (is_string($item)) {
                $key = $form_map[$item];
                $key = $key = "id__{$registration_form_id}__{$key}";
                $search[] = strtolower("(content->>'$.$key' IS NULL OR content->>'$.$key' = '')");
                continue;
            }

            $key = $item[0];
            unset($item[0]);

            $value = implode("','", $item);
            $value = "('$value')";
            $key = $form_map[$key];
            $key = "id__{$registration_form_id}__{$key}";
            $search[] = strtolower("lower(content->>'$.$key') IN $value");
        }

        $search = implode(' AND ', $search);
        $search = "AND $search";
    }
    else {
        $search = [];

        foreach ($form_map_keys as $key) {
            $key = $form_map[$key];
            $key = "id__{$registration_form_id}__{$key}";
            $search[] = strtolower("lower(content->>'$.$key') like '%$search_query%'");
        }

        $search = implode(' OR ', $search);
        $search = "AND ($search)";
    }
}

if (!$query && $_GET['sort']) {
    if ($_GET['sort'] === 'id') {
        $order = "id ";
    }
    else {
        $order = "content->>'$.id__{$_GET['sort']}' ";
    }

    $order .= strtolower($_GET['order'] ?? '') === 'desc' ? 'DESC' : 'ASC';

    $query = "SELECT *, (SELECT count(*) FROM `data` WHERE type = 'response' AND content->>'$.chatbot' = '{$chatbot['slug']}' $search) as count
        FROM `data` 
WHERE `type` = 'response' AND 
      content->>'$.chatbot' = '{$chatbot['slug']}' 
      $search
order by $order 
$query_limit";
}

if (!$query) {
    $query = "SELECT *, (SELECT count(*) FROM `data` WHERE type = 'response' AND content->>'$.chatbot' = '{$chatbot['slug']}' $search) as count
        FROM `data` 
WHERE `type` = 'response' AND 
      content->>'$.chatbot' = '{$chatbot['slug']}'
      $search
ORDER BY id DESC
$query_limit";
}

$responses = $sql->executeSQL($query);
$responses_count = $responses[0]['count'] ?? null;
$pages_count = ceil($responses_count / $upper_limit);

if (!$responses) {
    if (!$search) {
        echo "No responses for this chatbot yet";
        die();
    }
}

$responses = $dash->doContentCleanup($responses);

if (isset($_GET['export'])) {
    $columns[] = 'id';
    $csv_array = array();
    $columns = array_merge($columns, $form_map_keys);

    foreach ($responses as $response) {
        $_temp = array();

        foreach($columns as $column) {
            $_key = $column == 'id' ? $column : "id__{$registration_form_id}__{$form_map[$column]}";
            $_temp[$column] = $response[$_key] ?? '';
        }

        $csv_array[] = $_temp;
    }

    $fn->array_to_csv($csv_array, array_keys($csv_array[0]), "export_responses_{$chatbot['slug']}");
    die();
}

require_once THEME_PATH . '/pages/_header.php';
?>

<div class="container pt-5">
    <h1><?= $chatbot['title'] ?></h1>
</div>

<div class="container mt-5 pb-5">
    <div>
        <form id="search_form" class="col-lg-5 mx-auto">
            <div class="input-group">
                <div class="form-floating flex-fill">
                    <input type="search"
                           id="search"
                           class="form-control"
                           name="search"
                           placeholder="Search (Re-phrase supported)"
                           value="<?= urldecode($search_query ?? '') ?>">
                    <label for="search">Search (Re-phrase supported)</label>
                </div>

                <button class="btn btn-lg btn-primary-custom" type="submit"><i class="far fa-search"></i></button>
            </div>
        </form>
    </div>

    <p class="small text-muted text-end mt-3">(Total: <?php echo $responses_count ?? 0 ?>)</p>

    <div class="table-wrapper">
        <table id="responses-table" class="table table-hover table-bordered mt-3 overflow-auto">
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
                    <th class="cursor-pointer sortable <?= $active_class ?>" data-sort="id" data-order="<?= $order ?>"># <?= $arrow ?></th>
                    <?php
                    foreach ($form_map_keys as $key) {
                        $sort_key = "{$registration_form_id}__{$form_map[$key]}";

                        $active_class = '';
                        $arrow = '';
                        $order = 'desc';

                        if ($sort_key === ($_GET['sort'] ?? null)) {
                            $active_class = 'active';
                            $arrow = strtolower($_GET['order'] ?? '') === 'desc' ? "<i class='fas fa-arrow-down'></i>" : "<i class='fas fa-arrow-up'></i>";

                            $order = strtolower($_GET['order'] ?? '') === 'desc' ? 'asc' : 'desc';
                        }

                        echo "<th class='text-capitalize text-center cursor-pointer sortable $active_class' data-order='$order' data-sort='$sort_key'>$key $arrow</th>";
                    }
                    if (($_SESSION['role'] ?? null) == 'admin') {
                        echo "<th></th>";
                    }
                    ?>
                </tr>
            </thead>

            <tbody>
            <?php
            if ($responses === 0) {
                echo "<tr><td colspan='100%' class='text-center'>No records to show</td></tr>";
            }
            foreach ($responses as $response) {
                $td = "<th>{$response['id']}</th>";

                foreach ($form_map_keys as $key) {
                    $form_key = "id__{$registration_form_id}__{$form_map[$key]}";

                    $key_cap = ucfirst($key);
                    $innerText =  isset($response[$form_key]) ? "$response[$form_key]" : '';
                    $td .= "<td class='text-center' data-name='{$key}_{$response['id']}' title='$key_cap'>$innerText</td>";
                }

                $_edit_button = ($_SESSION['role'] ?? null) == 'admin' ?
                    "<td class='text-center'><button class='btn btn-outline-dark edit-form' data-id='{$response['id']}'><i class='far fa-edit'></i></button></td>" :
                    '';
                echo "<tr>$td $_edit_button</tr>";
            }
            ?>
            </tbody>
        </table>
    </div>

    <nav aria-label="Page navigation">
        <div class="d-flex justify-content-between">
            <button id="export-table" class="btn btn-primary-custom"><i class="far fa-file-export"></i> Export</button>
            <p class="small text-muted text-end">(Total: <?php echo $responses_count ?? 0 ?>)</p>
        </div>
        <ul class="pagination mt-4 justify-content-center flex-wrap">
            <?php
            $active_class = $page == 1 ? 'disabled' : '';
            $target = $fn->update_query_string('page', $page-1);
            echo "<li class='page-item $active_class'><a class='page-link' href='$target' target='_self'><i class='far fa-chevron-left'></i></a></li>";

            if ($pages_count <= 10) {
                for ($i = 1; $i <= $pages_count; $i++) {
                    $active_class = $page == $i ? 'active' : '';
                    $target = $fn->update_query_string('page', $i);
                    echo "<li class='page-item $active_class'><a class='page-link' href='$target' target='_self'>$i</a></li>";
                }
            }
            else {
                // 1,2,3,4,5...11
                if ($page < 5) {
                    for ($i=1; $i <= 5; $i++) {
                        $active_class = $page == $i ? 'active' : '';
                        $target = $fn->update_query_string('page', $i);
                        echo "<li class='page-item $active_class'><a class='page-link' href='$target' target='_self'>$i</a></li>";
                    }

                    echo "<li class='page-item disabled'><a class='page-link' href='#'>...</li>";
                    $target = $fn->update_query_string('page', $pages_count);
                    echo "<li class='page-item'><a class='page-link' href='$target' target='_self'>$pages_count</li>";
                }
                // 1...7,8,9,10,11;
                elseif ($page <= $pages_count && $page >= $pages_count - 4) {
                    $target = $fn->update_query_string('page', 1);
                    echo "<li class='page-item'><a class='page-link' href='$target' target='_self'>1</a></li>";
                    echo "<li class='page-item disabled'><a class='page-link' href='#'>...</li>";

                    for ($i = $pages_count - 5; $i <= $pages_count; $i++) {
                        $active_class = $page == $i ? 'active' : '';
                        $target = $fn->update_query_string('page', $i);
                        echo "<li class='page-item $active_class'><a class='page-link' href='$target' target='_self'>$i</a></li>";
                    }
                }
                // 1...6,7,8...11
                else {
                    $target = $fn->update_query_string('page', 1);
                    echo "<li class='page-item'><a class='page-link' href='$target' target='_self'>1</a></li>";
                    echo "<li class='page-item disabled'><a class='page-link' href='#'>...</li>";


                    for ($i = $page-2; $i <= $page+2; $i++) {
                        $active_class = $page == $i ? 'active' : '';
                        $target = $fn->update_query_string('page', $i);
                        echo "<li class='page-item $active_class'><a class='page-link' href='$target' target='_self'>$i</a></li>";
                    }

                    echo "<li class='page-item disabled'><a class='page-link' href='#'>...</li>";
                    $target = $fn->update_query_string('page', $pages_count);
                    echo "<li class='page-item'><a class='page-link' href='$target' target='_self'>$pages_count</a></li>";
                }
            }

            $active_class = $page == $pages_count ? 'disabled' : 'enabled';
            $target = $fn->update_query_string('page', $page+1);
            echo "<li class='page-item $active_class'><a class='page-link' href='$target' target='_self'><i class='fas fa-chevron-right'></i></a></li>";
            ?>
        </ul>
    </nav>
</div>

<div id="edit-form-modal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="">
                <div class="modal-header">
                    <h5 class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="id" name="id">
                    <input type="hidden" name="chatbot_id" value="<?= $_GET['id'] ?? 0?>">

                    <?php
                    foreach ($form_map_keys as $key) {
                        $key_cap = ucfirst($key);
                        echo "<div class='form-floating mb-3'>
                                <input type='text' class='form-control' name='$key' id='$key' placeholder='$key_cap'>
                                <label for='$key'>$key_cap</label>
                            </div>";
                    }
                    ?>

                    <div class="text-center">
                        <span class="badge bg-success text-white d-none"><i class="fas fa-check"></i> Saved</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="far fa-ban"></i> Discard</button>
                    <button type="submit" class="btn btn-outline-success"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once THEME_PATH . '/pages/_footer.php';
