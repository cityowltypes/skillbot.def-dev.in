<?php
/**
 * @var object $dash;
 */

if (!isset($_GET['id'])) {
    header('Location: /report');
    die();
}

include_once __DIR__ . '/../_header.php';
require_once 'includes/_nav.php';

try {
    $map_data = $dash->get_ids(['type' => 'map', 'chatbot_id' => $_GET['id']], '=', 'AND');
    $map_data = $dash->getObjects($map_data);
    $map_data = array_pop($map_data);
    $map_data = array_filter($map_data, function ($value, $key) {
        if (trim($value)) {
            return $key;
        }

        return null;
    }, ARRAY_FILTER_USE_BOTH);
}
finally {
    if (!$map_data) {
        header('Location: /report');
        die();
    }
}

$ignore = ['id', 'slug', 'type', 'class', 'title', 'chatbot_id', 'redirect_uri', 'content_privacy', 'created_on', 'updated_on'];
$map_data = array_diff_key($map_data, array_flip($ignore));
$map_data = array_keys($map_data);
$map_data = json_encode($map_data);
echo "<script>const valid_map_keys = JSON.parse('$map_data')</script>"
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
        <div id="detailed-analytics" class="d-flex justify-content-center py-5 mt-5"></div>
    </div>
</div>

<?php include_once __DIR__ . '/../_footer.php'?>