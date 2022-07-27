<?php
/**
 * @var array $bot;
 */
use \Wildfire\Api;
use \Wildfire\Core\Dash;
use \Wildfire\Core\MySQL;
use \Wildfire\Theme\Functions;
use \Wildfire\Core\Console as console;

$dash = new Dash;
$sql = new MySQL();
$functions = new Functions;

$api = new Api();

$bot = $dash->getObject($_GET['id']);

/** IF BOT DOESN'T EXIST, SHOW PLACEHOLDER AND TERMINATE */
if (!$bot || ($bot['type'] ?? '') !== 'chatbot') {
    require_once "analytics/_placeholder_bots.php";
    die();
}

$module_and_form = $functions->derephrase($bot['module_and_form_ids']);

$registration_form = $sql->executeSQL("select * from data where type='form' and id={$module_and_form[0]} limit 1");
$registration_form = $dash->doContentCleanup($registration_form);


$registration_form = array_pop($registration_form);
$state_list = $functions->derephrase($registration_form['questions'][1])[0][1] ?? null;
$category_list = $functions->derephrase($registration_form['questions'][4])[0] ?? null;

if ($category_list) {
    unset($category_list[0]);
    $category_list = array_values($category_list);
    $category_list = strtolower(implode("','", $category_list));
}

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

$district = urldecode($_GET['district']) ?? null;

$map_states = $dash->get_ids(['type' => 'map', 'chatbot_id' => $_GET['id']], '=', 'AND');
$map_states = $dash->getObjects($map_states);
$map_states = array_pop($map_states);
$state = strtolower($map_states[$_GET['state']]) ?? null;
$data['state'] = $state;

if ($state) {
    $data['encodedState'] = urlencode($data['state']);
}

/** LIST OF VALID DISTRICTS IF STATE IS SELECTED AND DISTRICT ISN'T */
if ($state && !$district) {
    $districts = array_keys($state_list[$state]);
    $districts = strtolower(implode("','", $districts));
}

$age_group = "content->>'$.id__5__6' between 10 and 100";

if (trim($bot['min_age'] ?? '') && trim($bot['max_age'] ?? '')) {
    $age_group = "content->>'$.id__5__6' between {$bot['min_age']} and {$bot['max_age']}";
}

/** BY AGE */
if (!$state && !isset($_GET['state'])) {
    $data['users_by_age'] = $sql->executeSQL("SELECT content->>'$.id__5__6' as 'age', count(content->>'$.id__5__6') as age_count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            {$age_group}
        group by age having age_count > 5
        order by age
    ");
}
elseif (!$district) {
    $data['users_by_age'] = $sql->executeSQL("SELECT content->>'$.id__5__6' as 'age', count(content->>'$.id__5__6') as age_count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') IN ('{$districts}') AND
            {$age_group}
        group by age having age_count > 5
        order by age
    ");
}
else {
    $data['users_by_age'] = $sql->executeSQL("SELECT content->>'$.id__5__6' as 'age', count(content->>'$.id__5__6') as age_count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') = '{$district}' and
            {$age_group}
        group by age having age_count > 5
        order by age
    ");
}

foreach ($data['users_by_age'] as $age) {
    $temp[] = is_valid_number($age);
}

$data['users_by_age'] = array_filter($temp);

/** AVERAGE AGE AND TOTAL NUMBER OF USERS */
$user_age = 0;
$data['user_count'] = 0;
foreach ($data['users_by_age'] as $user) {
    $user_age += $user['age'] * $user['age_count'];
    $data['user_count'] += $user['age_count'];
}

$data['average_age'] = floor($user_age / $data['user_count']);
unset($user_age);

/** BY GENDER */
if (!$state) {
    $data['users_per_gender'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__8') as 'sex', count(content->>'$.id__5__8') as count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            (lower(content->>'$.id__5__8') = 'male' || lower(content->>'$.id__5__8') = 'female') and
            {$age_group}
        group by sex
    ");
}
elseif (!$district) {
    $data['users_per_gender'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__8') as 'sex', count(content->>'$.id__5__8') as count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__2') = '{$state}' and
            (lower(content->>'$.id__5__8') = 'male' || lower(content->>'$.id__5__8') = 'female') and
            {$age_group}
        group by sex
    ");
}
else {
    $data['users_per_gender'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__8') as 'sex', count(content->>'$.id__5__8') as count FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') = '{$district}' and
            (lower(content->>'$.id__5__8') = 'male' || lower(content->>'$.id__5__8') = 'female') and
            {$age_group}
        group by sex
    ");
}

/** BY CATEGORY */
if (!$state) {
    $data['users_per_category']['male'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' AND
            content->>'$.id__5__5' IS NOT NULL AND 
            NOT content->>'$.id__5__5' = '/start' AND
            lower(content->>'$.id__5__8') = 'male' AND
            {$age_group}
        group by category having count(content->>'$.id__5__5') > 50
    ");
    $data['users_per_category']['female'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' AND
            content->>'$.id__5__5' IS NOT NULL AND 
            NOT content->>'$.id__5__5' = '/start' AND
            lower(content->>'$.id__5__8') = 'female' AND
            {$age_group}
        group by category having count(content->>'$.id__5__5') > 50
    ");
}
elseif (!$district) {
    $data['users_per_category']['male'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__5') IN ('{$category_list}') AND
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__8') = 'male' AND
            {$age_group}
        group by category having count(content->>'$.id__5__5') > 10
    ");
    $data['users_per_category']['female'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__5') IN ('{$category_list}') AND
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__8') = 'female' AND
            {$age_group}
        group by category having count(content->>'$.id__5__5') > 10
    ");
}
else {
    $data['users_per_category']['male'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__5') IN ('{$category_list}') AND
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') = '{$district}' and
            lower(content->>'$.id__5__8') = 'male' AND
            {$age_group}
        group by category having count(content->>'$.id__5__5') > 10
    ");
    $data['users_per_category']['female'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__5') as category, count(lower(content->>'$.id__5__5')) as 'count' FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__5') IN ('{$category_list}') AND
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') = '{$district}' and
            lower(content->>'$.id__5__8') = 'female' AND
            {$age_group}
        group by category having count(content->>'$.id__5__5') > 10
    ");
}

$data['users_per_category']['labels'] = array_merge(
    array_column($data['users_per_category']['female'], 'category'),
    array_column($data['users_per_category']['male'], 'category')
);
$data['users_per_category']['labels'] = array_unique($data['users_per_category']['labels']);
ksort($data['users_per_category']['labels']);

$overlay = array_fill(0, count($data['users_per_category']['labels']), NULL);

foreach (['male', 'female'] as $sex) {
    $data['users_per_category'][$sex] = array_combine(
        array_column($data['users_per_category'][$sex], 'category'),
        array_column($data['users_per_category'][$sex], 'count')
    );
    ksort($data['users_per_category'][$sex]);

    unset($temp);
    foreach ($data['users_per_category']['labels'] as $label) {
        $temp[$label] = $data['users_per_category'][$sex][$label] ?? 0;
    }

    $data['users_per_category'][$sex] = array_values($temp ?? []);
}

/** BY MODULE */
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
                {$age_group} and
                {$bot_module_ids[$key]} = 1
        ")[0]['count'];
    }
    elseif ($state && !$district) {
        $data['per_module_users'][$_id]['count'] = $sql->executeSQL("SELECT count(*) as count from data
            where content->>'$.chatbot' = '{$bot['slug']}' and
                type = 'response' and
                {$age_group} and
                lower(content->>'$.id__5__2') = '{$state}' and
                {$bot_module_ids[$key]} = 1
        ")[0]['count'];
    }
    elseif ($state && $district) {
        $data['per_module_users'][$_id]['count'] = $sql->executeSQL("SELECT count(*) as count from data
            where content->>'$.chatbot' = '{$bot['slug']}' and
                type = 'response' and
                {$age_group} and
                lower(content->>'$.id__5__2') = '{$state}' and
                lower(content->>'$.id__5__3') = '{$district}' and
                {$bot_module_ids[$key]} = 1
        ")[0]['count'];
    }
}

/** CERTIFIED USERS */
$_search_pattern = [];

foreach ($bot_module_ids as $_json_key) {
    $_search_pattern[] = "{$_json_key} = 1";
}

$_search_pattern = implode(' and ', $_search_pattern);
if (!$state) {
    $data['users_who_completed_all'] = $sql->executeSQL("SELECT count(*) as count from data
        where type = 'response' and
            {$age_group} and
            content->>'$.chatbot' = '{$bot['slug']}' and
            {$_search_pattern}
    ")[0]['count'] ?? null;
}
elseif (!$district) {
    $data['users_who_completed_all'] = $sql->executeSQL("SELECT count(*) as count from data
        where type = 'response' and
            {$age_group} and
            content->>'$.chatbot' = '{$bot['slug']}' and
            {$_search_pattern} and
            lower(content->>'$.id__5__2') = '{$state}'
    ")[0]['count'] ?? null;
}
else {
    $data['users_who_completed_all'] = $sql->executeSQL("SELECT count(*) as count from data
        where type = 'response' and
            {$age_group} and
            content->>'$.chatbot' = '{$bot['slug']}' and
            {$_search_pattern} and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') = '{$district}'
    ")[0]['count'] ?? null;
}

/** BY DISTRICT */
if ($state && !$district) {
    $data['users_by_district'] = $sql->executeSQL("SELECT lower(content->>'$.id__5__3') as 'district', count(content->>'$.id__5__3') as 'count' FROM `data`
        where type = 'response' and
            {$age_group} and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__5__2') = '{$state}' and
            lower(content->>'$.id__5__3') IN ('{$districts}') AND
            content->>'$.id__5__6' between 10 and 100
        group by district having count > 8
        order by district
    ");
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

if (($_GET['interface'] ?? false) === 'api') {
    if ($api->method('get')) {
        $api->json($data)->send();
    }

    $api->send(400);
}

require_once "analytics/_analytics_ui.php";
