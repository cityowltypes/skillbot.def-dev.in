<?php include_once __DIR__ . '/../_header.php'?>

<div class="container py-5">
	<h1 class="fw-light text-center mb-3">Insights for <a target="new" href="https://t.me/<?=$_GET['handle']?>" class="fw-bold text-success">@<?=$_GET['handle']?></a></h1>
	<a href="/report/user-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>" class="w-100 d-block btn btn-primary my-2 btn-lg"><i class="fad fa-download"></i>&nbsp;User Data</a>
	<a href="/report/pre-assessment-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>" class="w-100 d-block btn btn-primary my-2 btn-lg"><i class="fad fa-download"></i>&nbsp;Pre-assessment Data</a>
	<a href="/report/post-assessment-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>" class="w-100 d-block btn btn-primary my-2 btn-lg"><i class="fad fa-download"></i>&nbsp;Post-assessment Data</a>
	<a href="/report" class="w-100 d-block btn btn-warning my-2 btn-lg">&larr;&nbsp;Go back</a>
</div>

<hr>

<div class="container py-5">
	<img src="/theme/assets/img/india.svg" class="w-100 mx-auto">
</div>
<?php include_once __DIR__ . '/../_footer.php'?>