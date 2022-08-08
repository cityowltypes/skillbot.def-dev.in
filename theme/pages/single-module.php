<?php
/**
 * @var object $functions
 * @var array $postdata
 */

use \Wildfire\Core\Console as console;

include_once __DIR__ . '/_header.php';

$data = array();
$data['title'] = $functions->derephrase($postdata['title']);
$data['intro'] = $functions->derephrase($postdata['intro_message'] ?? '');
$data['end'] = $functions->derephrase($postdata['end_message'] ?? '');
$data['form_ids'] = $functions->derephrase($postdata['level_and_form_ids'] ?? '');
?>
<section class="container py-5">
    <h3>Title</h3>
    <ul>
        <?php
        foreach($data['title'] as $head) {
            echo "<li>$head</li>";
        }
        ?>
    </ul>

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
        <ol>
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
        </ol>
    </div>
</section>
<?php include_once __DIR__ . '/_footer.php'?>
