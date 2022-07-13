<?php
/**
 * @var object $dash
 * @var object $sql
 * @var object $functions
 */
use \Wildfire\Core\Console as console;

require_once THEME_PATH . '/pages/_header.php';

$bot = $dash->getObject($_GET['chatbot_id']);

// if bot doesn't exist, show placeholder and terminate
if (!$bot || ($bot['type'] ?? '') !== 'chatbot') {
    require_once "_analytics_placeholder.php";
    die();
}

$module_and_form = $functions->derephrase($bot['module_and_form_ids']);

// console::debug($bot);
// $bot_details = $sql->executeSQL("select * from data where type='response' and content->>'$.chatbot'='{$bot['slug']}' limit 0,10");
// $registration_form = $sql->executeSQL("select * from data where type='form' and id={$module_and_form[0]} limit 1");
// $registration_form = $dash->doContentCleanup($registration_form);

// users by age
$data['users_by_age'] = $sql->executeSQL("SELECT content->>'$.id__5__6' as 'age', count(content->>'$.id__5__6') as age_count FROM `data`
    where type = 'response' and
        content->>'$.chatbot' = 'digital-financial-inclusion' and
        content->>'$.id__5__6' between 10 and 100
    group by age having age_count > 10
    order by age
");

function is_valid_number ($array) {
    if (filter_var($array['age'], FILTER_VALIDATE_INT) !== false) {
        $array['age'] = floor($array['age']);
        return $array;
    }
    else {
        return null;
    }
}

foreach ($data['users_by_age'] as $age) {
    $temp[] = is_valid_number($age);
}

$data['users_by_age'] = array_filter($temp);
// sort($data['users_by_age']);
// console::json($data['users_by_age']);

// average age of users and total number of users
$user_age = 0;
$data['user_count'] = 0;
foreach ($data['users_by_age'] as $user) {
    $user_age += $user['age'] * $user['age_count'];
    $data['user_count'] += $user['age_count'];
}

$data['average_age'] = floor($user_age / $data['user_count']);
unset($user_age);

// number of users per gender
$data['users_per_gender'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__8') as 'sex', count(content->>'$.id__5__8') as count FROM `data`
    where type = 'response' and
        content->>'$.chatbot' = 'digital-financial-inclusion' and
        (lower(content->>'$.id__5__8') = 'male' || lower(content->>'$.id__5__8') = 'female')
    group by sex
");

// number of users per category
$data['users_per_category'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
    where type = 'response' and
        content->>'$.chatbot' = 'digital-financial-inclusion' and
        not content->>'$.id__5__5' is null and
        not content->>'$.id__5__5' = '/start'
    group by category having count(content->>'$.id__5__5') > 15
");
// console::debug($data['users_per_category']);

// per module the number of users who've completed
$data['per_module_users'] = null;
$bot_module_ids = array_map('trim', explode(',', $bot['module_ids']));

foreach ($bot_module_ids as $key => $_id) {
    $bot_module_ids[$key] = "content->>'$.completed__{$_id}'";

    // get title of module
    $data['per_module_users'][$_id]['module_name'] = $dash->getAttribute($_id, 'title');
    $data['per_module_users'][$_id]['module_name'] = $functions->derephrase($data['per_module_users'][$_id]['module_name'])[0];

    // get count of users who've completed these modules
    $data['per_module_users'][$_id]['count'] = $sql->executeSQL("SELECT count(*) as count from data
        where content->>'$.chatbot' = '{$bot['slug']}' and
            type = 'response' and
            {$bot_module_ids[$key]} = 1
    ")[0]['count'];
}
// console::debug($data['per_module_users']);

// number of users who've completed all modules
$_search_pattern = [];

foreach ($bot_module_ids as $_json_key) {
    $_search_pattern[] = "{$_json_key} = 1";
}

$_search_pattern = implode(' and ', $_search_pattern);
$data['users_who_completed_all'] = $sql->executeSQL("SELECT count(*) as count from data
    where type = 'response' and
        content->>'$.chatbot' = '{$bot['slug']}' and
        {$_search_pattern}
")[0]['count'] ?? null;
// console::debug($data['users_who_completed_all']);

// console::debug($registration_form);
// console::debug(array_map('json_decode', array_column($bot_details, 'content')));
// console::json($data);

function format_to_thousands(int $value) {
    return number_format($value, 0, '.', ',');
}
?>
<div class="py-5 container">
    <nav>
        <div class="row justify-content-around">
            <?php
            $_scopes = ['overall', 'state', 'district'];
            foreach ($_scopes as $scope) {
                if (($_GET['scope'] ?? 'overall') === $scope) {
                    echo "<a class='col-3 btn btn-primary text-capitalize' href='#'>{$scope}</a>";
                }
                else {
                    echo "<a class='col-3 btn btn-outline-primary text-capitalize' href='?chatbot_id={$_GET['chatbot_id']}&scope={$scope}'>{$scope}</a>";
                }
            }
            ?>
        </div>
    </nav>

    <input type="hidden" name="is_analytics" value="true">

    <!-- total number of users -->
    <section class="py-5 mt-5">
        <p class='display-2 text-center'><?= format_to_thousands($data['user_count']) ?></p>
        <p class='text-uppercase text-center'>total users</p>
    </section>

    <hr class="my-5">

    <!-- users by age and average age -->
    <section>
        <p class="display-4 text-center">Age Related</p>

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
        <p class="display-4 text-center">Module Related</p>

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
        <p class="display-4 text-center">Category Related</p>

        <div class="row mt-5">
            <div class="col-lg-8 mx-auto">
                <canvas id="users_per_category" width="400" height="400"></canvas>
            </div>
        </div>
    </section>

    <hr class="my-5">

    <!-- users per sex -->
    <section class="container">
        <p class="display-4 text-center">Users by Sex</p>

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