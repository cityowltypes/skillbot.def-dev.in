<?php
/**
 * @var object $dash;
 * @var object $functions;
 */
include_once THEME_PATH . '/pages/_header.php';

use \Theme\PdfForm;
use \Wildfire\Core\Console as cc;


$response = $dash->getObject($_GET['response_id']);
$chatbot = $dash->getObject($_GET['chatbot_id']);
$items = $functions->derephrase($chatbot['module_and_form_ids'], 1);
$registration_form_id = array_keys($items)[0];
$name_ques_id = trim($chatbot['certificate_user_name_question_number']) ?: 1;

$i = 0;
$incomplete = 0;

foreach ($items as $module_id=>$assessment_form_id) {
    if ($i && $module_id && !isset($response['completed__'.$module_id]))
      $incomplete = 1;
    $i++;
}

if ($response['id__'.$registration_form_id.'__'.$name_ques_id] && (!$incomplete || ($chatbot['allow_incomplete_certificate_download'] ?? false))) {
    $form_data = [
        'name' => $response["id__{$registration_form_id}__{$name_ques_id}"]
    ];

    if (
      isset($chatbot['certificate_url']) &&
      trim($chatbot['certificate_url']) !== ''
    ) {
      $certificate_template = trim($chatbot['certificate_url']);
    }
    else {
      $certificate_template = THEME_PATH . '/docs/certificate.pdf';
    }

    $pdf_form = new PdfForm($certificate_template, $form_data);

    $output_file = time() . '.pdf';

    $pdf_form
      ->flatten()
      ->save("/tmp/$output_file")
      ->download();

    die();
}
?>
<div class="card border-danger border-5 rounded-0" style="width: 100vw; height: 100vh;">
  <div class="d-flex align-content-between flex-wrap justify-content-center card-body text-center">
    <div class="card-title fw-light small text-muted text-uppercase border-bottom border-muted w-100 pb-3"><i class="fal fa-crosshairs"></i>&nbsp;Capture Screenshot<br>Response ID: <?=$response['id']?></div>

    <div class="pb-5">
          <h1 class="card-title text-uppercase fw-bold mb-5"><span class="text-danger">Incomplete.</span><br><br>Please complete the course.</h1>
    </div>
    <div class="pb-5">
        <img src="<?=$chatbot['logos_footer']?>" class="img-fluid">
    </div>
  </div>
</div>
<?php include_once THEME_PATH . '/pages/_footer.php';?>
