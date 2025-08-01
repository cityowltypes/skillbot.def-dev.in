<?php
/**
 * @var object $sql
 * @var object $dash
 * @var object $functions
 * @var array $state_list
 * @var array $registration_form
 * @var string $state
 * @var string $district
 */

$selected_state = null;

foreach ($state_list as $state_name => $lc) {
    $v = urlencode(strtolower($state_name));
    $is_selected = urlencode(($state) ?? '') == $v ? 'selected' : '';

    if (urlencode($state ?? '') == $v) {
        $selected_state = $state_name;
    }

    if (strlen($state_name) > 2) {
        $state_name = ucwords(strtolower($state_name));
    } else {
        $state_name = strtoupper($state_name);
    }
}
?>

<div class="container py-5">
    <h1 class="display-6 text-center">Filter</h1>

    <div class='col-lg-12 mx-auto mt-3'>
        <form id="region_filter" class="row align-items-center justify-content-center" action="" method="get">
            <?php if (isset($state) && $selected_state) : ?>
            <div class="col-lg-4 border rounded p-3 shadow-sm">
                <div class="mb-3">
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

                <div class="row justify-content-between">
                    <?php
                    // set current date as max selectable date
                    $date = new DateTime();
                    $today = $date->format('Y-m-d');
                    unset($date);
                    ?>
                    <div class="mb-3 col-lg-6">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input id="start_date" class="form-control" type="date"
                               name="start_date" max="<?=$today?>" value="<?=$_GET['start_date'] ?? ''?>"
                               required>
                    </div>

                    <div class="mb-3 col-lg-6">
                        <label for="end_date" class="form-label">End Date</label>
                        <input id="end_date" class="form-control" type="date"
                               name="end_date" max="<?=$today?>" value="<?=$_GET['end_date'] ?? ''?>"
                               required>
                    </div>
                </div>

                <hr>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary-custom ms-auto px-3">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
            <?php endif ?>
        </form>
    </div>
</div>
