<?php
/**
 * @var array $data
 * @var object $functions
 */
?>

<div class="container-fluid">
    <?php
    // render filter ui only if request uri doesn't have no_filter set
    if (!isset($_GET['no_filter']) && $data['user_count'] !== 0 && isset($_GET['state'])) {
        require_once "filter.php";
    }
    ?>

    <div id="analytics-container" class="d-flex flex-column justify-content-center align-items-center">
        <?php
        // if there's no data to show for the selection
        if ($data['user_count'] == 0) {
            require_once "_placeholder_stats.php";
            die();
        }
        ?>

        <input type="hidden" name="is_analytics" value="true">

        <hr class="my-5 bg-transparent">

        <!-- total number of users -->
        <section class="widget-wrapper container align-items-start">
            <?php
            if (isset($data['users_by_district'])):
            ?>
            <div class="card px-0 shadow-sm">
                <div class="card-header small fw-bold">Users by district</div>

                <div class="card-body">
                    <canvas id="users_by_district" width="300" height="300"></canvas>
                </div>

                <div class="card-footer text-muted">
                    Total users: <?= $functions->format_to_thousands($data['user_count']) ?>
                </div>
            </div>
            <?php
            endif
            ?>

            <?php
            if (!isset($data['users_by_district']) && isset($_GET['district'])):
            ?>
            <div class="card px-0 shadow-sm">
                <div class="card-header small fw-bold">Total users in district</div>

                <div class="card-body">
                    <div class="card-text display-2 text-center"><?= $functions->format_to_thousands($data['user_count']) ?></div>
                </div>
            </div>
            <?php
            endif;
            ?>

            <div class="card px-0 shadow-sm">
                <div class="card-header small fw-bold">Distribution by Age</div>

                <div class="card-body">
                    <canvas id="users_by_age" width="400" height="400"></canvas>
                </div>

                <div class="card-footer text-muted">
                    Average age: <?= $data['average_age'] ?>
                </div>
            </div>

            <div class="card px-0 shadow-sm">
                <div class="card-header small fw-bold">
                    Distribution by Module
                </div>

                <div class="card-body">
                    <canvas id="users_per_module" width="400" height="400"></canvas>
                </div>

                <!-- users who completed all modules -->
                <div class="card-footer text-muted">
                    <?= $functions->format_to_thousands($data['users_who_completed_all']) ?> users certified
                </div>
            </div>

            <div class="card px-0 shadow-sm">
                <div class="card-header small fw-bold">Distribution by Category</div>

                <div class="card-body">
                    <canvas id="users_per_category" width="400" height="400"></canvas>
                </div>
            </div>

            <div class="card px-0 shadow-sm">
                <div class="card-header small fw-bold">Distribution by Gender</div>

                <div class="card-body">
                    <canvas id="users_per_sex" width="400" height="400"></canvas>
                </div>
            </div>
        </section>
    </div>
</div>

<?php
require_once THEME_PATH . '/pages/_footer.php';
