<?php include_once __DIR__ . '/_header.php'?>
<pre>
<?php
$heads = $functions->reph1($postdata['title']);
$questions = array();
foreach($postdata['questions'] as $i=>$m){
    $questions[$i] = $functions->reph1($m);
}
$responses = array();
foreach($postdata['response_options'] as $i=>$m){
    $responses[$i] = $functions->reph1($m);
}
print_r($responses);
?>
</pre>
<section class="container d-flex">

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th scope="col">#</th>
                <?php
                foreach($heads as $head):
                ?>
                <th scope="col"><?=$head[0]?></th>
                <?php
                endforeach;
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach($messages as $i=>$message):
            ?>
            <tr>
                <th scope="row"><?=$i+1?></th>
                <?php
                foreach($message as $m):
                ?>
                <td><?=$m[0]?></td>
                <?php
                endforeach;
                ?>
                
            </tr>
            <?php
            endforeach;
            ?>
            
        </tbody>
    </table>
</section>
<?php include_once __DIR__ . '/_footer.php'?>