<?php
/**
 * @var array $data
 */
?>

<div class="py-5 container">
    <?php
    require_once "_state.php";

    // if there's no data to show for the selection
    if ($data['user_count'] == 0) {
        require_once "_placeholder_stats.php";
        die();
    }
    ?>

    <input type="hidden" name="is_analytics" value="true">

    <!-- total number of users -->
    <section class="py-5 mt-5">
        <?php
        if (isset($data['users_by_district'])):
        ?>
        <div class="row">
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
    </section>

    <hr class="my-5">

    <!-- users by age and average age -->
    <section>
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
    </section>

    <hr class="my-5">

    <!-- users per module -->
    <section>
        <p class="display-4 text-center">Distribution by Module</p>

        <div class="row mt-5">
            <div class="col-lg-6 mx-auto">
                <canvas id="users_per_module" width="400" height="400"></canvas>
            </div>
        </div>

        <!-- users who completed all modules -->
        <div class="mt-5 text-center">
            <p class='display-2 mb-0'><?= format_to_thousands($data['users_who_completed_all']) ?></p>
            <p class='text-uppercase mb-0'>users completed all modules</p>
        </div>
    </section>

    <hr class="my-5">

    <!-- users per category -->
    <section class="container">
        <p class="display-4 text-center">Distribution by Category</p>

        <div class="row mt-5">
            <div class="col-lg-8 mx-auto">
                <canvas id="users_per_category" width="400" height="400"></canvas>
            </div>
        </div>
    </section>

    <hr class="my-5">

    <!-- users per sex -->
    <section class="container pb-5">
        <p class="display-4 text-center">Distribution by Gender</p>

        <div class="row mt-5">
            <div class="col-lg-6 mx-auto">
                <canvas id="users_per_sex" width="400" height="400"></canvas>
            </div>
        </div>
    </section>
</div>

<script>
    let analytics_data = <?= json_encode($data) ?>;
</script>

<?php
require_once THEME_PATH . '/pages/_footer.php';