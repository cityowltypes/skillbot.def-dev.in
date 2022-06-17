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

<div class="card border-danger border-5 rounded-0" style="width: 100vw; height: 100vh;">
  <div class="d-flex align-content-between flex-wrap justify-content-center card-body text-center">
    <div class="card-title fw-light small text-muted text-uppercase border-bottom border-muted w-100 pb-3"><i class="fal fa-crosshairs"></i>&nbsp;Capture Screenshot<br>Response ID: <?=$response['id']?></div>
    
    <div class="pb-5">
      <?php if ($response['id__'.$registration_form_id.'__'.$name_ques_id] && !$incomplete) { ?>
          <h1 class="card-title text-uppercase fw-bold mb-5">Certificate<br><span class="small">of Completion</span></h1>
          <h5 class="card-text fw-light">This is to certify</h5>
          <h5 class="card-text fw-bold"><u><?=$response['id__'.$registration_form_id.'__'.$name_ques_id]?></u></h5>
          <h5 class="card-text fw-light">has successfully completed <strong><?=$chatbot['certificate_programme']?></strong> course by <strong><?=$chatbot['certificate_funder']?></strong>.<br><br><em><?=date('d, M Y')?></em></h5>
        <?php } else { ?>
          <h1 class="card-title text-uppercase fw-bold mb-5"><span class="text-danger">Incomplete.</span><br><br>Please complete the course.</h1>
        <?php } ?>

    </div>
    <div class="pb-5">
        <img src="<?=$chatbot['logos_footer']?>" class="img-fluid">
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/_footer.php'?>