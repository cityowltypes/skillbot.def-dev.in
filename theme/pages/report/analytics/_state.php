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

                        if (strlen($state) > 2) {
                            $state = ucwords(strtolower($state));
                        }
                        else {
                            strtoupper($state);
                        }

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