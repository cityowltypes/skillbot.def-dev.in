<?php
/**
 * @var object $functions
 * @var array $postdata
 */

use \Wildfire\Core\Console as console;

include_once __DIR__ . '/_header.php';

$heads = $functions->derephrase($postdata['title']);
$messages = array();
foreach($postdata['messages'] as $i=>$m){
    $messages[$i] = $functions->derephrase($m);
}
?>
<section class="container py-5">
    <h3>Title</h3>
    <ul>
        <?php
        foreach($heads as $head) {
            echo "<li>$head</li>";
        }
        ?>
    </ul>

    <h3 class="mt-5">Messages</h3>
    <div class="row align-items-start flex-column">
        <?php
        foreach ($messages as $key => $message) {
            $card_body = '';

            $index = $key + 1;

            echo "<span class='fw-bold mt-3'>$index) </span>";
            if (gettype($message[0]) === 'array') {
                foreach ($message[0] as $msg) {
                    if (strstr($msg, '.jpg') !== false) {
                        $card_body .= "<a href='$msg' class='me-2 mb-2 d-inline-block' target='_blank'><img class='img-thumbnail small-thumb' src='$msg' alt=''/></a>";
                    }
                }

                echo "<div class='col-lg-3 pt-2 pe-0 mb-2'>$card_body</div>";
            }
            elseif (gettype($message[0]) === 'string') {
                foreach ($message as $msg) {
                    $card_body .= "<li class='mb-2'>$msg</li>";
                }

                echo "<div class='me-2 mb-2'><ol type='a'>$card_body</ol></div>";
            }

        }
        ?>
    </div>
</section>
<?php include_once __DIR__ . '/_footer.php'?>
