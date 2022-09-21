<?php
/**
 * @var array $postdata
 * @var object $dash
 * @var object $functions
 */

include_once __DIR__ . '/_header.php';
?>

<div class="container d-flex" style="min-height: 50vh;">

  <div class="row align-items-center w-100"><div class="mx-auto col-12">

		<h1 class="my-5">View Response <?=$postdata['id']?></h1>
		<div class="col-12 single-post-body" data-id="<?=$postdata['id']?>">
			<?php
			foreach ($postdata as $key => $value) {
				if (strstr($key, 'completed__') !== false) {
					$module = $dash->getAttribute(explode('completed__', $key)[1], 'title');
					$modules .= '<div class="fs-4 fw-bold"><span class="fas fa-check text-success"></span> '.$functions->derephrase($module)[0].'</div>';
				}


				if (strstr($key, '__score') !== false) {
					$form = $dash->getAttribute(explode('__', $key)[1], 'title');
					$number_of_questions = count(json_decode($dash->getAttribute(explode('__', $key)[1], 'questions'), true));
					$key = $functions->derephrase($form)[0];
					$forms[$key] = '<div class="fs-6 fw-bold">'.$functions->derephrase($form)[0].' <span class="badge badge-pill text-white bg-primary">'.$value.' / '.$number_of_questions.'</span></div>';
				}

			}
			ksort($forms);
			?>
			<div class="my-5"><?=$modules?></div>
			<div class="my-5"><?=implode('', $forms)?></div>
		</div>

		<hr class="my-5" />

		<div class="text-uppercase fw-bold">Raw Data Dump</div>
		<div style="overflow-x: scroll;">
			<pre>
				<?php print_r($postdata) ?>
			</pre>
		</div>

	</div></div>
</div>
<?php include_once __DIR__ . '/_footer.php'?>
