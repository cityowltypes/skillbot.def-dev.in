<?php
use \Wildfire\Api;
use \Wildfire\Core\Dash;
use \Wildfire\Core\MySQL;
use \Wildfire\Theme\Functions;
use \Wildfire\Core\Console as console;

$dash = new Dash;
$sql = new MySQL();
$functions = new Functions;

$api = new Api();

// variables defined for this
$bot = null; // chatbot
$data = null; // data to be used for plotting graphs
$state = null; // requested state
$form_map = null; // form fields mapped to labels of registration form
$district = null; // requested district
$districts = null; // csv of districts
$age_group = null; // age limiter
$state_list = null; // list of states
$map_states = null; // valid states to be marked on map
$date_range = null; // date limiter
$category_list = null; // list of categories
$registration_form = null; // module's registration form
$registration_form_id = null; // id of registration form

$bot = $dash->getObject($_GET['id']);

/** IF BOT DOESN'T EXIST, SHOW PLACEHOLDER AND TERMINATE */
if (!$bot || ($bot['type'] ?? '') !== 'chatbot') {
    require_once THEME_PATH . "/views/analytics/placeholder/bot.php";
    die();
}

$form_map = $functions->get_form_map($bot);
$registration_form = $functions->get_registration_form($bot);
$registration_form_id = $registration_form['id'];

$state_list = null;
if (is_numeric($form_map['state'])) {
    $state_list = $functions->derephrase($registration_form['questions'][$form_map['state'] - 1])[0][1];
}

$category_list = null;
if (is_numeric($form_map['category'])) {
    $category_list = $functions->derephrase($registration_form['questions'][$form_map['category'] - 1])[0];
}


$date_range = '';
if (isset($_GET['start_date'], $_GET['end_date'])) {
    $start_date = strtotime($_GET['start_date']);
    $end_date = strtotime($_GET['end_date']);
    $date_range = "AND created_on BETWEEN {$start_date} AND {$end_date}";
}

if ($category_list) {
    unset($category_list[0]);
    $category_list = array_values($category_list);
    $category_list = strtolower(implode("','", $category_list));
}

if ($state_list) {
    $csv_handle = fopen($state_list, 'r');
    $state_list = [];

    while ($temp = fgetcsv($csv_handle, 0, ',')) {
        $state_list[$temp[0]][$temp[1]][] = $temp[2];
    }
    unset($temp, $state_list['state']);
    $state_list = strtolower(json_encode($state_list));
    $state_list = json_decode($state_list, 1);
}

$district = null;
if (isset($_GET['district'])) {
    $district = trim(urldecode($_GET['district']));
}

$map_states = $dash->get_ids(['type' => 'map', 'chatbot_id' => $_GET['id']], '=', 'AND');
$map_states = $dash->getObjects($map_states);
$map_states = array_pop($map_states);

$state = null;
if (isset($_GET['state']) && $map_states[$_GET['state']]) {
    $state = strtolower($map_states[$_GET['state']]);
}
$data['state'] = $state;

if ($state) {
    $data['encodedState'] = urlencode($data['state']);
}

/** LIST OF VALID DISTRICTS IF STATE IS SELECTED AND DISTRICT ISN'T */
$districts = '';
if ($state && !$district) {
    $districts = array_keys($state_list[$state]);
    $districts = strtolower(implode("','", $districts));
    $districts = "lower(content->>'$.id__{$registration_form_id}__{$form_map['district']}') IN ('{$districts}') AND";
}

$age_group = "content->>'$.id__{$registration_form_id}__{$form_map['age']}' between 10 and 100";

if (trim($bot['min_age'] ?? '') && trim($bot['max_age'] ?? '')) {
    $age_group = "content->>'$.id__{$registration_form_id}__{$form_map['age']}' between {$bot['min_age']} and {$bot['max_age']}";
}

/** BY AGE */
if (!$state && !isset($_GET['state'])) {
    $data['users_by_age'] = $sql->executeSQL("
        SELECT
               content->>'$.id__{$registration_form_id}__{$form_map['age']}' as 'age',
               count(content->>'$.id__{$registration_form_id}__{$form_map['age']}') as age_count
        FROM `data`
        WHERE
              type = 'response' and
              content->>'$.chatbot' = '{$bot['slug']}' and
              {$age_group}
              {$date_range}
        group by age having age_count > 1
        order by age
    ");
}
elseif (!$district && isset($districts)) {
    $data['users_by_age'] = $sql->executeSQL("
        SELECT
               content->>'$.id__{$registration_form_id}__{$form_map['age']}' as 'age',
               count(content->>'$.id__{$registration_form_id}__{$form_map['age']}') as age_count
        FROM data
        where
              type = 'response' and
              content->>'$.chatbot' = '{$bot['slug']}' and
              lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
              {$districts} {$age_group} {$date_range}
        group by age having age_count > 5
        order by age
    ");
}
else {
    $data['users_by_age'] = $sql->executeSQL("
        SELECT
               content->>'$.id__{$registration_form_id}__{$form_map['age']}' as 'age',
               count(content->>'$.id__{$registration_form_id}__{$form_map['age']}') as age_count
        FROM `data`
        where
              type = 'response' and
              content->>'$.chatbot' = '{$bot['slug']}' and
              lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
              lower(content->>'$.id__{$registration_form_id}__{$form_map['district']}') = ('{$district}') AND
              {$age_group} {$date_range}
        group by age having age_count > 5
        order by age
    ");
}

unset($temp);
foreach ($data['users_by_age'] as $age) {
    $temp[] = is_valid_number($age);
}

if (isset($temp)) {
    $data['users_by_age'] = array_filter($temp);
}

/** AVERAGE AGE AND TOTAL NUMBER OF USERS */
$user_age = 0;
$data['user_count'] = 0;
if (isset($state) && trim($state) && !$district) {
    $query = "SELECT count(*) as count from data WHERE type = 'response' AND
        content->>'$.chatbot' = '{$bot['slug']}' AND
        lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
        {$districts} {$age_group} {$date_range}";
}
elseif (isset($district) && trim($district)) {
    $query = "SELECT count(*) as count from data WHERE type = 'response' AND
        content->>'$.chatbot' = '{$bot['slug']}' AND
        lower(content->>'$.id__{$registration_form_id}__{$form_map['district']}') = '{$district}' and
        lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
        {$age_group} {$date_range}";
}
else {
    $query = "SELECT count(*) as count from data WHERE type = 'response' AND
        content->>'$.chatbot' = '{$bot['slug']}' AND
        {$age_group} {$date_range}";
}

$data['user_count'] = $sql->executeSQL($query)[0]['count'];

foreach ($data['users_by_age'] as $user) {
    $user_age += $user['age'] * $user['age_count'];
}

$data['average_age'] = floor($user_age / $data['user_count']);
unset($user_age);

/** BY GENDER */
if (!$state) {
    $data['users_per_gender'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') as 'sex',
               count(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') as count
        FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            (
                lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'male' OR
                lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'female'
            ) and
            {$age_group} {$date_range}
        group by sex
    ");
}
elseif (!$district) {
    $data['users_per_gender'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') as 'sex',
               count(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') as count
        FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
            (
                lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'male' OR
                lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'female'
            ) and
            {$age_group} {$date_range}
        group by sex
    ");
}
else {
    $data['users_per_gender'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') as 'sex',
               count(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') as count
        FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['district']}') = '{$district}' and
            (
                lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'male' OR
                lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'female'
            ) and
            {$age_group} {$date_range}
        group by sex
    ");
}

if (!$data['users_per_gender']) {
    unset($data['users_per_gender']);
}

/** BY CATEGORY */
if (!$state) {
    $data['users_per_category']['male'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') as category,
               count(lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}')) as 'count'
        FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' AND
            content->>'$.id__{$registration_form_id}__{$form_map['category']}' IS NOT NULL AND
            NOT content->>'$.id__{$registration_form_id}__{$form_map['category']}' = '/start' AND
            lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'male' AND
            {$age_group} {$date_range}
        group by category having count(content->>'$.id__{$registration_form_id}__{$form_map['category']}') > 50
    ");
    $data['users_per_category']['female'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') as category,
               count(lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}')) as 'count'
        FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' AND
            content->>'$.id__{$registration_form_id}__{$form_map['category']}' IS NOT NULL AND
            NOT content->>'$.id__{$registration_form_id}__{$form_map['category']}' = '/start' AND
            lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'female' AND
            {$age_group} {$date_range}
        group by category having count(content->>'$.id__{$registration_form_id}__{$form_map['category']}') > 50
    ");
}
elseif (!$district) {
    $data['users_per_category']['male'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') as category,
               count(lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}')) as 'count'
        FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') IN ('{$category_list}') AND
            lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'male' AND
            {$age_group} {$date_range}
        group by category having count(content->>'$.id__{$registration_form_id}__{$form_map['category']}') > 10
    ");
    $data['users_per_category']['female'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') as category,
               count(lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}')) as 'count'
        FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') IN ('{$category_list}') AND
            lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'female' AND
            {$age_group} {$date_range}
        group by category having count(content->>'$.id__{$registration_form_id}__{$form_map['category']}') > 10
    ");
}
else {
    $data['users_per_category']['male'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') as category,
               count(lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}')) as 'count'
        FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') IN ('{$category_list}') AND
            lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['district']}') = '{$district}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'male' AND
            {$age_group} {$date_range}
        group by category having count(content->>'$.id__{$registration_form_id}__{$form_map['category']}') > 10
    ");
    $data['users_per_category']['female'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') as category,
               count(lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}')) as 'count'
        FROM `data`
        where type = 'response' and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['category']}') IN ('{$category_list}') AND
            lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['district']}') = '{$district}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['gender']}') = 'female' AND
            {$age_group} {$date_range}
        group by category having count(content->>'$.id__{$registration_form_id}__{$form_map['category']}') > 10
    ");
}

if (!($data['users_per_category']['male'] && $data['users_per_category']['female'])) {
    unset($data['users_per_category']);
}

if (isset($data['users_per_category'])) {
    $data['users_per_category']['labels'] = array_merge(
        array_column($data['users_per_category']['female'], 'category'),
        array_column($data['users_per_category']['male'], 'category')
    );
    $data['users_per_category']['labels'] = array_unique($data['users_per_category']['labels']);
    ksort($data['users_per_category']['labels']);
    $data['users_per_category']['labels'] = array_filter($data['users_per_category']['labels']);

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
                {$date_range}
        ")[0]['count'];
    }
    elseif ($state && !$district) {
        $data['per_module_users'][$_id]['count'] = $sql->executeSQL("SELECT count(*) as count from data
            where content->>'$.chatbot' = '{$bot['slug']}' and
                type = 'response' and
                {$age_group} and
                lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
                {$bot_module_ids[$key]} = 1
                {$date_range}
        ")[0]['count'];
    }
    elseif ($state && $district) {
        $data['per_module_users'][$_id]['count'] = $sql->executeSQL("SELECT count(*) as count from data
            where content->>'$.chatbot' = '{$bot['slug']}' and
                type = 'response' and
                {$age_group} and
                lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
                lower(content->>'$.id__{$registration_form_id}__{$form_map['district']}') = '{$district}' and
                {$bot_module_ids[$key]} = 1
                {$date_range}
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
            {$_search_pattern} {$date_range}
    ")[0]['count'] ?? null;
}
elseif (!$district) {
    $data['users_who_completed_all'] = $sql->executeSQL("SELECT count(*) as count from data
        where type = 'response' and
            {$age_group} and
            content->>'$.chatbot' = '{$bot['slug']}' and
            {$_search_pattern} and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}'
            {$date_range}
    ")[0]['count'] ?? null;
}
else {
    $data['users_who_completed_all'] = $sql->executeSQL("SELECT count(*) as count from data
        where type = 'response' and
            {$age_group} and
            content->>'$.chatbot' = '{$bot['slug']}' and
            {$_search_pattern} and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['district']}') = '{$district}'
            {$date_range}
    ")[0]['count'] ?? null;
}

/** BY DISTRICT */
if ($state && !$district) {
    $data['users_by_district'] = $sql->executeSQL("
        SELECT
               lower(content->>'$.id__{$registration_form_id}__{$form_map['district']}') as 'district',
               count(content->>'$.id__{$registration_form_id}__{$form_map['district']}') as 'count'
        FROM `data`
        where type = 'response' and
            {$age_group} and
            content->>'$.chatbot' = '{$bot['slug']}' and
            lower(content->>'$.id__{$registration_form_id}__{$form_map['state']}') = '{$state}' and
            {$districts} {$age_group}  {$date_range}
        group by district having count > 1
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

require_once THEME_PATH . "/views/analytics/main.php";
