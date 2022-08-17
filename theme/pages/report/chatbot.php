<?php
/**
 * @var object $dash;
 */

use \Wildfire\Core\Console as console;
use \Wildfire\Theme\Functions;

// if chatbot id isn't provided return to /report
if (!isset($_GET['id'])) {
    header('Location: /report');
    die();
}

include_once __DIR__ . '/../_header.php';
require_once 'includes/_nav.php';

/**
 * Get map for given chatbot (should work only if chatbot id is valid)
 */
try {
    $bot = $dash->getObject($_GET['id']);
    $map_data = $dash->get_ids(['type' => 'map', 'chatbot_id' => $_GET['id']], '=', 'AND');
    $map_data = $dash->getObjects($map_data);
    $map_data = array_pop($map_data);
    $map_data = array_filter($map_data, function ($value, $key) {
        if (trim($value)) {
            return $key;
        }

        return null;
    }, ARRAY_FILTER_USE_BOTH);

    $form_map = $dash->get_ids(['chatbot' => $bot['slug']], '=');
    $form_map = array_pop($form_map);
    $form_map = $dash->getObject($form_map['id']);
}
finally {
    if (!($map_data && $bot)) {
        header('Location: /report');
        die();
    }

    if ($form_map === 0) {
        require_once THEME_PATH . "/views/analytics/placeholder/form_map.php";
        die();
    }
}

$fn = new Functions();
$bot['languages'] = $fn->derephrase($bot['languages']);

$ignore = ['id', 'slug', 'type', 'class', 'title', 'chatbot_id', 'redirect_uri', 'content_privacy', 'created_on', 'updated_on'];
$map_data = array_diff_key($map_data, array_flip($ignore));
$map_data = array_keys($map_data);
$map_data = json_encode($map_data);
echo "<script>const valid_map_keys = JSON.parse('$map_data')</script>";

$background_color = $bot['background_color'] ?? 'var(--bs-white)';
$primary_color = $bot['primary_color'] ?? 'var(--bs-primary)';
$text_color = $bot['text_color'] ?? 'var(--bs-black)';
$inactive_color = $bot['inactive_color'] ?? 'var(--bs-gray-400)';

echo "<style>
:root {
    --background-color: {$bot['background_color']};
    --primary-color: {$bot['primary_color']};
    --text-color: {$bot['text_color']};
    --inactive-color: {$bot['inactive_color']};
}
</style>";
?>

<div class="main">
    <?php
    if (($_SESSION['role_slug'] ?? null) === 'admin') {
        require_once 'includes/_nav_aside.php';
    }
    ?>

    <div id="dash-viewport" class="pb-5 pt-2">
        <a
                href="<?= "/report/chatbot?id={$_GET['id']}&handle={$_GET['handle']}" ?>"
                class="btn rounded-0 text-start text-decoration-none">
            <i class="fad fa-redo me-2"></i>Reset & Reload
        </a>
        <?php if (trim($bot['logos_footer'] ?? '')): ?>
        <div class="container my-5 pb-lg-5">
            <div class="col-lg-8 mx-auto">
                <img src="<?= trim($bot['logos_footer']) ?>" alt="Partner banner" class="img-fluid partner-banner">
            </div>
        </div>
        <?php endif; ?>
        <!-- Map and State stats -->
        <div class="row">
            <div class='col-11 col-lg-6 px-2 map mx-auto mb-5 mb-lg-0 me-lg-0 ms-lg-auto'>
                <?php
                $map = file_get_contents(THEME_PATH . "/assets/img/india.svg");
                echo $map;
                ?>
            </div>

            <div id="statDisplay" class="col-12 col-lg-4 me-lg-auto d-flex flex-column justify-content-center align-items-center">
                <div class="text-center">
                    <h1 id="stateName" class="fw-light display-2 text-capitalize text-theme"></h1>
                </div>

                <div class="text-center mt-5">
                    <p id="totalUsers" class="display-1 text-theme"></p>
                    <h2 class="h3 fw-light text-theme">Total Users</h2>
                </div>

                <div class="text-center mt-5">
                    <p id="averageAge" class="display-1 text-theme"></p>
                    <h2 class="h3 fw-light text-theme">Average age</h2>
                </div>

                <div class="text-center mt-5">
                    <i class="far fa-chevron-double-down fa-2x text-theme"></i>
                </div>
            </div>
        </div>

        <hr class="bg-transparent py-5">
        <hr class="bg-transparent py-5">

        <!-- Detailed analytics for selected state -->
        <div id="detailed-analytics" class="d-flex justify-content-center py-5"></div>
    </div>
</div>

<?php include_once __DIR__ . '/../_footer.php'?>
