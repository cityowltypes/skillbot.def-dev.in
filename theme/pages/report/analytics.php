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
    require_once "analytics/_placeholder_bots.php";
    die();
}

$module_and_form = $functions->derephrase($bot['module_and_form_ids']);

// console::debug($bot);
// $bot_details = $sql->executeSQL("select * from data where type='response' and content->>'$.chatbot'='{$bot['slug']}' limit 0,10");
$registration_form = $sql->executeSQL("select * from data where type='form' and id={$module_and_form[0]} limit 1");
$registration_form = $dash->doContentCleanup($registration_form);


$registration_form = array_pop($registration_form);
$state_list = $functions->derephrase($registration_form['questions'][1])[0][1] ?? null;

if (!$state_list) {
    require_once "analytics/_placeholder_bots.php";
    die();
}

$csv_handle = fopen($state_list, 'r');
$state_list = [];

while ($temp = fgetcsv($csv_handle, 0, ',')) {
    $state_list[$temp[0]][$temp[1]][] = $temp[2];
}
unset($temp, $state_list['state']);
$state_list = strtolower(json_encode($state_list));
$state_list = json_decode($state_list, 1);

$state = urldecode($_GET['state']) ?? null;
$district = urldecode($_GET['district']) ?? null;

// list of valid districts if state is selected and district isn't
if ($state && !$district) {
    $districts = array_keys($state_list[$state]);
    $districts = implode("','", $districts);
}

// users by age
if (!$state) {
    $data['users_by_age'] = $sql->executeSQL("SELECT content->>'$.id__5__6' as 'age', count(content->>'$.id__5__6') as age_count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = 'digital-financial-inclusion' and
            content->>'$.id__5__6' between 10 and 100
        group by age
        order by age
    ");
}
elseif ($state && !$district) {
    $data['users_by_age'] = $sql->executeSQL("SELECT content->>'$.id__5__6' as 'age', count(content->>'$.id__5__6') as age_count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = 'digital-financial-inclusion' and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') IN ('{$districts}') AND
            content->>'$.id__5__6' between 10 and 100
        group by age
        order by age
    ");
}
else if ($state && $district) {
    $data['users_by_age'] = $sql->executeSQL("SELECT content->>'$.id__5__6' as 'age', count(content->>'$.id__5__6') as age_count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = 'digital-financial-inclusion' and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') = '{$district}' and
            content->>'$.id__5__6' between 10 and 100
        group by age
        order by age
    ");
}

foreach ($data['users_by_age'] as $age) {
    $temp[] = is_valid_number($age);
}

$data['users_by_age'] = array_filter($temp);
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
if (!$state) {
    $data['users_per_gender'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__8') as 'sex', count(content->>'$.id__5__8') as count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = 'digital-financial-inclusion' and
            (lower(content->>'$.id__5__8') = 'male' || lower(content->>'$.id__5__8') = 'female')
        group by sex
    ");
}
elseif ($state && !$district) {
    $data['users_per_gender'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__8') as 'sex', count(content->>'$.id__5__8') as count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = 'digital-financial-inclusion' and
            lower(content->>'$.id__5__2') = '{$state}' and
            (lower(content->>'$.id__5__8') = 'male' || lower(content->>'$.id__5__8') = 'female')
        group by sex
    ");
}
elseif ($state && $district) {
    $data['users_per_gender'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__8') as 'sex', count(content->>'$.id__5__8') as count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = 'digital-financial-inclusion' and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') = '{$district}' and
            (lower(content->>'$.id__5__8') = 'male' || lower(content->>'$.id__5__8') = 'female')
        group by sex
    ");
}

// number of users per category
if (!$state) {
    $data['users_per_category'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = 'digital-financial-inclusion' and
            not content->>'$.id__5__5' is null and
            not content->>'$.id__5__5' = '/start'
        group by category having count(content->>'$.id__5__5') > 10
    ");
}
elseif ($state && !$district) {
    $data['users_per_category'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = 'digital-financial-inclusion' and
            not content->>'$.id__5__5' is null and
            not content->>'$.id__5__5' = '/start' and
            lower(content->>'$.id__5__2') = '{$state}'
        group by category having count(content->>'$.id__5__5') > 10
    ");
}
elseif ($state && $district) {
    $data['users_per_category'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = 'digital-financial-inclusion' and
            not content->>'$.id__5__5' is null and
            not content->>'$.id__5__5' = '/start' and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') = '{$district}'
        group by category having count(content->>'$.id__5__5') > 10
    ");
}
// console::debug($data['users_per_category']);

// per module the number of users who've completed
$data['per_module_users'] = null;
$bot_module_ids = $functions->derephrase($bot['module_and_form_ids']);
$bot_module_ids = array_column($bot_module_ids, 0);
$bot_module_ids = array_map('trim', $bot_module_ids);

foreach ($bot_module_ids as $key => $_id) {
    $bot_module_ids[$key] = "content->>'$.completed__{$_id}'";

    // get title of module
    $data['per_module_users'][$_id]['module_name'] = $dash->getAttribute($_id, 'title');
    $data['per_module_users'][$_id]['module_name'] = $functions->derephrase($data['per_module_users'][$_id]['module_name'])[0];

    // get count of users who've completed these modules
    if (!$state) {
        $data['per_module_users'][$_id]['count'] = $sql->executeSQL("SELECT count(*) as count from data
            where content->>'$.chatbot' = '{$bot['slug']}' and
                type = 'response' and
                {$bot_module_ids[$key]} = 1
        ")[0]['count'];
    }
    elseif ($state && !$district) {
        $data['per_module_users'][$_id]['count'] = $sql->executeSQL("SELECT count(*) as count from data
            where content->>'$.chatbot' = '{$bot['slug']}' and
                type = 'response' and
                lower(content->>'$.id__5__2') = '{$state}' and
                {$bot_module_ids[$key]} = 1
        ")[0]['count'];
    }
    elseif ($state && $district) {
        $data['per_module_users'][$_id]['count'] = $sql->executeSQL("SELECT count(*) as count from data
            where content->>'$.chatbot' = '{$bot['slug']}' and
                type = 'response' and
                lower(content->>'$.id__5__2') = '{$state}' and
                lower(content->>'$.id__5__3') = '{$district}' and
                {$bot_module_ids[$key]} = 1
        ")[0]['count'];
    }
}
// console::debug($data['per_module_users']);

// number of users who've completed all modules
$_search_pattern = [];

foreach ($bot_module_ids as $_json_key) {
    $_search_pattern[] = "{$_json_key} = 1";
}

$_search_pattern = implode(' and ', $_search_pattern);
if (!$state) {
    $data['users_who_completed_all'] = $sql->executeSQL("SELECT count(*) as count from data
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            {$_search_pattern}
    ")[0]['count'] ?? null;
}
elseif ($state && !$district) {
    $data['users_who_completed_all'] = $sql->executeSQL("SELECT count(*) as count from data
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            {$_search_pattern} and
            lower(content->>'$.id__5__2') = '{$state}'
    ")[0]['count'] ?? null;
}
elseif ($state && $district) {
    $data['users_who_completed_all'] = $sql->executeSQL("SELECT count(*) as count from data
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            {$_search_pattern} and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') = '{$district}'
    ")[0]['count'] ?? null;
}

// district wise users for state
if ($state && !$district) {
    $data['users_by_district'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__3') as 'district', count(content->>'$.id__5__3') as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') IN ('{$districts}') AND
            content->>'$.id__5__6' between 10 and 100
        group by district having count > 8
        order by district
    ");
}

function format_to_thousands(int $value) {
    return number_format($value, 0, '.', ',');
}

function is_valid_number ($array) {
    if (filter_var($array['age'], FILTER_VALIDATE_INT) !== false) {
        $array['age'] = floor($array['age']);
        return $array;
    }
    else {
        return null;
    }
}
?>
<div class="py-5 container">
    <?php
    require_once "analytics/_state.php";

    // if there's no data to show for the selection
    if ($data['user_count'] == 0) {
        require_once "analytics/_placeholder_stats.php";
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