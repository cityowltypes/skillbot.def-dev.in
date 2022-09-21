
<?php
include_once THEME_PATH . '/pages/_header.php';
$query = $sql->executeSQL("SELECT `content` FROM `data` WHERE `type` = 'chatbot';");
foreach($query as $key=>$val){
    $query[$key] = json_decode($val['content'], true);
}

if(isset($_POST['token']) && isset($_POST['chatbot'])){
    $call = json_decode($dash->do_shell_command("curl https://api.telegram.org/bot".$_POST['token']."/setWebhook?url=https://skillbot.def-dev.in/chatbot/".$_POST['chatbot'].""), true);
    print_r($call);
}

?>

<section class="container d-flex justify-content-center mt-5 pt-5">
    <div class="d-flex flex-column align-items-center col-8">
        <form class="col-12 d-flex flex-column align-items-center" action="?" method="post">
            <div class="form-floating col-12">
                    <select class="form-select w-100" id="floatingSelect" name="chatbot" aria-label="Select chatbot">
                        <option disabled selected>Select</option>
                        <?php
                        foreach($query as $i):
                        ?>
                        <option value="<?= $i['slug'] ?>"><?= $i['title']?></option>
                        <?php
                        endforeach;
                        ?>
                    </select>
                    <label for="floatingSelect">Select Chatbot</label>
                </div>
            <div class="form-floating my-3 col-12">
                <input type="text" class="form-control w-100" id="floatingInput" name="token" placeholder="Bot Token">
                <label for="floatingInput">Bot Token</label>
            </div>
            <button type="submit" class="btn btn-primary w-50 btn-lg">Set webhook</button>
            
        </form>
    </div>
</section>

<?php
include_once THEME_PATH . '/pages/_footer.php';
?>