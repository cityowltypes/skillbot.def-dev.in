<?php
/**
 * helps with importing CSVs for telegram chatbot
 */

require_once THEME_PATH . '/pages/_header.php';

use \Wildfire\Core\{Console as cc, Dash};

$dash = new Dash();
$error = null;
$success = null;

if (!isset($_POST['type']) && !empty($_FILES)) {
    $error = "Import type not selected";
}

if (!$error) {
    $files = $_FILES['files'];
    $tmp_names = $files['tmp_name'];

    foreach($tmp_names as $key => $file) {
        if ($files['error'][$key] !== 0) {
            continue;
        }

        $csv_records = array();
        $input = fopen($file, 'r');

        while (!feof($input)) {
            $csv_records[] = fgetcsv($input);
        }

        $csv_records = array_column($csv_records, 0);
        $title = str_replace(".csv", "", "{$_POST['title']} {$files['name'][$key]}");


        if ($_POST['type'] === 'form') {
            $form = [
                'type' => 'form',
                'title' => $title,
                'questions' => $csv_records,
                'content_privacy' => 'private'
            ];

            $success = $dash->pushObject($form);
        }
        else if ($_POST['type'] === 'chapter') {
            $chapter = [
                'type' => 'chapter',
                'title' => $title,
                'messages' => $csv_records,
                'content_privacy' => 'private'
            ];

            $success = $dash->pushObject($chapter);
        }
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <h1 class="text-center">Import</h1>

            <?php
            if ($success) :
            ?>
            <div class="alert alert-success mt-5" role="alert">
                <i class="fas fa-check"></i> Imported
            </div>
            <?php
            elseif ($error) :
            ?>
            <div class="alert alert-danger mt-5" role="alert">
                <i class="fas fa-times"></i> Failed
            </div>
            <?php
            endif;
            ?>

            <form method="post" enctype="multipart/form-data" class="mt-5">
                <div>
                    <select class="form-select" name="type" aria-label="Type" required>
                        <option selected disabled hidden value="">Select Import Type</option>
                        <option value="form">Form</option>
                        <option value="chapter">Chapter</option>
                    </select>
                </div>

                <div class="mt-3">
                    <label for="title" class="form-lable">Title</label>
                    <input type="text" name="title" id="title" class="form-control" placeholder="Title for record(s)">
                </div>

                <div class="mt-3">
                    <label for="files" class="form-label">Files</label>
                    <input type="file" name="files[]" id="files" class="form-control" accept=".csv" multiple required>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-outline-success">Import</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once THEME_PATH . '/pages/_footer.php';
