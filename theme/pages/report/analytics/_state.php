<?php
/**
 * @var object $sql
 * @var object $dash
 * @var object $functions
 * @var array $registration_form
 */

use \Wildfire\Core\Console as console;

$registration_form = array_pop($registration_form);
$state_list = $functions->derephrase($registration_form['questions'][1])[0][1] ?? null;

if (!$state_list) {
    require_once "_placeholder.php";
    die();
}

// $state_list = file_get_contents($state_list);
$csv_handle = fopen($state_list, 'r');
$state_list = [];

while ($temp = fgetcsv($csv_handle, 0, ',')) {
    $state_list[$temp[0]][$temp[1]][] = $temp[2];
}
unset($temp, $state_list['state']);
// console::debug($state_list);
?>
<div class="container py-5">
    <h1 class="display-6 text-center">Filter</h1>

    <div class='col-lg-12 mx-auto mt-3'>
        <form id="region_filter" class="row align-items-center justify-content-center" action="" method="get">
            <div class="col-lg-4 mb-3">
                <select class="form-select" name="state">
                    <option value='all' selected>All states</option>
                    <?php
                    $selected_state = null;

                    foreach ($state_list as $state => $lc) {
                        $v = urlencode(strtolower($state));
                        $is_selected = ($_GET['state'] ?? '') == $v ? 'selected' : '';

                        if (($_GET['state'] ?? '') == $v) {
                            $selected_state = $state;
                        }

                        $state = ucwords(strtolower($state));

                        echo "<option value='{$v}' class='text-capitalize' {$is_selected}>{$state}</option>";
                    }
                    ?>
                </select>
            </div>

            <?php
            if (isset($_GET['state']) && $selected_state):
            ?>
            <div class="col-lg-4 mb-3">
                <select class="form-select" name="district">
                    <option value='all' selected>All districts</option>
                    <?php
                    foreach ($state_list[$selected_state] as $district => $lc) {
                        $v = urlencode(strtolower($district));
                        $is_selected = ($_GET['district'] ?? '') == $v ? 'selected' : '';
                        $district = ucwords(strtolower($district));

                        echo "<option value='{$v}' class='text-capitalize' {$is_selected}>{$district}</option>";
                    }
                    ?>
                </select>
            </div>
            <?php
            endif
            ?>
        </form>
    </div>
</div>