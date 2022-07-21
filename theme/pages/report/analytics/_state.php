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
?>

<div class="container py-5">
    <h1 class="display-6 text-center">Filter</h1>

    <div class='col-lg-12 mx-auto mt-3'>
        <form id="region_filter" class="row align-items-center justify-content-center" action="" method="get">
            <?php

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

                // echo "<option value='{$v}' class='text-capitalize' {$is_selected}>{$state_name}</option>";
            }

            if (isset($state) && $selected_state):
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