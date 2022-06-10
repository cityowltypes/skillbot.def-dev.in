
<?php
include_once THEME_PATH . '/pages/_header.php';
$query = $sql->executeSQL("SELECT `content` FROM `data` WHERE `type` = 'chatbot';");
foreach($query as $key=>$val){
    $query[$key] = json_decode($val['content'], true);
}

if(isset($_POST['token']) && isset($_POST['chatbot'])){
    
}

?>

<section class="container d-flex justify-content-center mt-5 pt-5">
    <div class="d-flex flex-column align-items-center col-8">
        <form class="col-12" action="">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="floatingInput" name="token" placeholder="Bot Token">
                <label for="floatingInput">Bot Token</label>
            </div>
            <div class="form-floating">
                <select class="form-select" id="floatingSelect" name="chatbot" aria-label="Select chatbot">
                    <option disabled selected>Select</option>
                    <?php
                    foreach($query as $i):
                    ?>
                    <option value="<?= $i['slug'] ?>"><?= $i['title'] ?></option>
                    <?php
                    endforeach;
                    ?>
                </select>
                <label for="floatingSelect">Select Chatbot</label>
            </div>
        </form>
    </div>
</section>

<?php
include_once THEME_PATH . '/pages/_footer.php';
?>