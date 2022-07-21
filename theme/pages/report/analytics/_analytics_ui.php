<?php
/**
 * @var array $data
 */
?>

<div class="py-5 container">
    <?php
    // render filter ui only if request uri doesn't have no_filter set
    if (!isset($_GET['no_filter']) && $data['user_count'] !== 0) {
        require_once "_state.php";
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
        <section class="container">
            <div class="card card-body py-5 col-lg-10 mx-auto shadow-sm">
                <?php
                if (isset($data['users_by_district'])):
                ?>
                <div class="row mb-5">
                    <div class="col-lg-10 mx-auto">
                        <canvas id="users_by_district" width="300" height="300"></canvas>
                    </div>
                </div>
                <?php
                endif
                ?>

                <div class="text-center">
                    <p class='display-2'><?= format_to_thousands($data['user_count']) ?></p>
                    <p class='text-uppercase'>total users</p>
                </div>
            </div>
        </section>

        <hr class="my-5 bg-transparent">

        <!-- users by age and average age -->
        <section class="container">
            <div class="card card-body py-5 col-lg-10 mx-auto shadow-sm">
                <p class="display-4 text-center">Distribution by Age</p>

                <div class="row mt-5">
                    <div class="col-lg-10 mx-auto">
                        <canvas id="users_by_age" width="400" height="400"></canvas>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <p class='display-2 mb-0'><?= $data['average_age'] ?></p>
                    <p class='text-uppercase mb-0'>average age</p>
                </div>
            </div>
        </section>

        <hr class="my-5 bg-transparent">

        <!-- users per module -->
        <section class="container">
            <div class="card card-body py-5 shadow-sm col-lg-10 mx-auto">
                <p class="display-4 text-center">Distribution by Module</p>

                <div class="row mt-5">
                    <div class="col-lg-7 mx-auto">
                        <canvas id="users_per_module" width="400" height="400"></canvas>
                    </div>
                </div>

                <!-- users who completed all modules -->
                <div class="mt-5 text-center">
                    <p class='display-2 mb-0'><?= format_to_thousands($data['users_who_completed_all']) ?></p>
                    <p class='text-uppercase mb-0'>users completed all modules</p>
                </div>
            </div>
        </section>

        <hr class="my-5 bg-transparent">

        <!-- users per category -->
        <section class="container">
            <div class="card card-body col-lg-10 mx-auto shadow-sm py-5">
                <p class="display-4 text-center">Distribution by Category</p>

                <div class="row mt-5">
                    <div class="col-lg-8 mx-auto">
                        <canvas id="users_per_category" width="400" height="400"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <hr class="my-5 bg-transparent">

        <!-- users per sex -->
        <section class="container pb-5">
            <div class="card card-body shadow-sm col-lg-10 py-5 mx-auto">
                <p class="display-4 text-center">Distribution by Gender</p>

                <div class="row mt-5">
                    <div class="col-lg-5 mx-auto">
                        <canvas id="users_per_sex" width="400" height="400"></canvas>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<?php
require_once THEME_PATH . '/pages/_footer.php';