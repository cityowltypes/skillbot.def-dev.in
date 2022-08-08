<?php
/**
 * @var object $functions
 * @var array $postdata
 */
require_once '_header.php';

use \Wildfire\Core\Console as cc;

$data['title'] = $functions->derephrase($postdata['title'] ?? '');

foreach ($postdata['questions'] as $question) {
    $data['questions'][] = $functions->derephrase($question ?? '');
}
$data['intro'] = $functions->derephrase($postdata['intro_message'] ?? '');
$data['end'] = $functions->derephrase($postdata['end_message'] ?? '');
?>
<section class="container py-5">
    <h3>Title</h3>
    <ul>
        <?php
        $title = implode('</li><li>', $data['title']);
        echo "<li>$title</li>";
        ?>
    </ul>

    <h3 class="mt-5">Questions</h3>
    <div>
        <div class="col-lg-7">
            <?php
            foreach ($data['questions'] as $key => $questions) {
                $list_items = implode('</li><li class="mt-2">', $questions);
                echo "<ol><li>$list_items</li></ol><hr/>";
            }
            ?>
        </div>
    </div>

    <h3 class="mt-5">Intro Messages</h3>
    <div class="container">
        <?php
        $list_items = '';

        foreach($data['intro'] as $message) {
            if (trim($message)) {
                $list_items .= "<li>$message</li>";
            }
        }

        if (trim($list_items)) {
            echo "<ol>$list_items</ol>";
        } else {
            echo "<h4 class='text-muted fw-light'>Nothing to show</h4>";
        }
        ?>
    </div>

    <h3 class="mt-5">End Messages</h3>
    <div class="container">
        <?php
        $list_items = '';

        foreach($data['end'] as $message) {
            if (trim($message)) {
                $list_items .= "<li>$message</li>";
            }
        }

        if (trim($list_items)) {
            echo "<ol>$list_items</ol>";
        } else {
            echo "<h4 class='text-muted fw-light'>Nothing to show</h4>";
        }
        ?>
    </div>
</section>
<?php include_once __DIR__ . '/_footer.php'?>
