<?php
include_once __DIR__ . '/../_header.php';
require_once 'includes/_nav.php';
?>

<div class="main">
    <?php
    require_once 'includes/_nav_aside.php';
    ?>

    <div id="dash-viewport" class="py-5">
        <!-- Map and State stats -->
        <div class="row">
            <div class='col-11 col-lg-6 px-2 map mx-auto me-lg-0 ms-lg-auto'>
                <?php
                $map = file_get_contents(THEME_PATH . "/assets/img/india.svg");
                echo $map;
                ?>
            </div>

            <div id="statDisplay" class="col-12 col-lg-4 me-lg-auto d-flex flex-column justify-content-center align-items-center">
                <div class="text-center">
                    <h1 id="stateName" class="fw-light text-capitalize"></h1>
                </div>

                <div class="text-center mt-5">
                    <p id="totalUsers" class="display-1"></p>
                    <h2 class="h3 fw-light">Total Users</h2>
                </div>

                <div class="text-center mt-5">
                    <p id="averageAge" class="display-1"></p>
                    <h2 class="h3 fw-light">Average age</h2>
                </div>

                <div class="text-center mt-5">
                    <span class="border border-2 border-secondary text-secondary px-2 rounded-circle btn-square">
                        <i class="fas fa-arrow-down"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- Detailed analytics for selected state -->
        <div id="detailed-analytics" class="d-flex justify-content-center py-5"></div>
    </div>
</div>

<?php include_once __DIR__ . '/../_footer.php'?>