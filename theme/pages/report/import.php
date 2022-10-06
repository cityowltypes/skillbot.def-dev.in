<?php
/**
 * This file helps with import of csv data to update existing response recrods
 */

use Symfony\Component\VarDumper\VarDumper;
use \Wildfire\Core\{Console as cc, Dash, MySQL};

include_once THEME_PATH . '/pages/_header.php';

$role_slug = $_SESSION['role_slug'] ?? null;
$allowed_roles = ['admin', 'chatbot_admin'];
$allowed_chatbots = $_SESSION['chatbot'] ?? null;

require_once 'includes/_nav.php';

// check for submissions and import
if (empty($_FILES)) {
    goto form_ui;
}

$uploaded_file = $_FILES['file'];

if ($uploaded_file['error'] !== 0) {
    $import_status = 'error';
    goto form_ui;
}

$import_status = 'ok';
$file_data = array();
$input = fopen($uploaded_file['tmp_name'], 'r');

while (!feof($input)) {
    $file_data[] = fgetcsv($input);
}

$csv_keys = $file_data[0];
unset($file_data[0]);

$file_data = array_values($file_data);

function set_array_keys($csv_keys, $values) {
    $final_array = array();

    foreach ($csv_keys as $index => $value) {
        if ($value === 'response_id') {
            $value = 'id';
        }
        $final_array[$value] = $values[$index];
    }

    return $final_array;
}

$sql = new MySQL();

// format csv values and create queries
$queries = array();
foreach ($file_data as $key => $value) {
    if (
        is_scalar($value) ||
        empty($value['id'])
    ) {
        continue;
    }

    $value = set_array_keys($csv_keys, $value);

    $json_set_values = array();

    foreach ($value as $k => $v) {
        $v = trim($v);

        $v = mysqli_real_escape_string($sql->databaseLink, $v);
        $json_set_values[] = "'$.$k', '$v'";
    }

    $json_set_values = implode(", ", $json_set_values);

    $queries[] = "UPDATE data SET content = JSON_SET(content, $json_set_values) where id = {$value['id']}";
}

$queries = implode(";", $queries);

// had to use this to run multiple queries on a single connection
$import_status = mysqli_multi_query($sql->databaseLink, $queries) ? 'ok' : 'error';

// to debug errors
cc::debug(mysqli_error($sql->databaseLink));

unset($queries);
?>

<?php
// form UI
form_ui:
?>
<div class="container py-5">
    <div class="row">
        <form action="" method="post" enctype="multipart/form-data" class="col-lg-7 mx-auto">
            <h1>Import updates</h1>

            <div class="mt-4">
                <label for="file" class="form-label">Select file to import</label>
                <input type="file" name="file" id="file" class="form-control" accept=".csv">
            </div>

            <?php if (!empty($_FILES)): ?>
                <?php if ($import_status === 'ok'): ?>
            <div class="mt-3 alert alert-success" role="alert">
                <i class="fas fa-check me-2"></i>Imported
            </div>
                <?php endif; ?>

                <?php if ($import_status === 'error'): ?>
            <div class="mt-3 alert alert-danger" role="alert">
                <i class="fas fa-times me-2"></i>Something went wrong, contact support
            </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-3">
                <button class="btn btn-primary" type="submit">Import</button>
            </div>
        </form>
    </div>
</div>

<?php
require_once THEME_PATH . '/pages/_footer.php';
