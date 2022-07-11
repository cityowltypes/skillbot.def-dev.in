<?php include_once __DIR__ . '/_header.php'?>
<?php
  $response = $dash->getObject($_GET['response_id']);
  $chatbot = $dash->getObject($_GET['chatbot_id']);
  $items = $functions->derephrase($chatbot['module_and_form_ids'], 1);
  $registration_form_id = array_keys($items)[0];
  $name_ques_id = ($chatbot['certificate_user_name_question_number'] ?? 1);

$i = 0;
$incomplete = 0;
foreach ($items as $module_id=>$assessment_form_id) {  
    if ($i && $module_id && !isset($response['completed__'.$module_id]))
      $incomplete = 1;
    $i++;
}
?>

<div class="d-flex align-content-center flex-wrap justify-content-center card border-danger border-5 rounded-0" style="width: 100vw; height: 100vh;">
  <div class="text-center certificate-font-body-1">
    <div class="pt-5">
      <?php if ($response['id__'.$registration_form_id.'__'.$name_ques_id] && !$incomplete) { ?>
          <h1 class="card-title display-3 fw-bold mb-5 certificate-font-body-1">Certificate of Completion</h1>
          <h5 class="card-text display-6 fw-light">This is to certify</h5>
          <h5 class="card-text display-6 fw-bold"><u><?=$response['id__'.$registration_form_id.'__'.$name_ques_id]?></u></h5>
          <h5 class="card-text display-6 fw-light w-75 mx-auto">has successfully completed <strong><?=$chatbot['certificate_programme']?></strong> course by <strong><?=$chatbot['certificate_funder']?></strong>.<br><br><em><?=date('d, M Y')?></em></h5>
        <?php } else { ?>
          <h1 class="card-title text-uppercase fw-bold mb-5"><span class="text-danger">Incomplete.</span><br><br>Please complete the course.</h1>
        <?php } ?>

      <div class="py-5">
          <img src="<?=$chatbot['logos_footer']?>" height="100">
      </div>

    </div>
  </div>
</div>

<?php include_once __DIR__ . '/_footer.php'?>