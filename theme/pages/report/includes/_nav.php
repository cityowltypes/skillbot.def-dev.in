<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container-fluid justify-content-start">
        <?php
        if (isset($_GET['handle'])):
        ?>
        <button id="toggle" class="hamburger ms-lg-2 px-0" data-expanded="false"><span></span></button>
        <a class="navbar-brand me-0 ms-3" href="<?= "/report/chatbot?id={$_GET['id']}&handle={$_GET['handle']}" ?>">@<?=$_GET['handle']?></a>
        <?php
        else:
        ?>
        <a class="navbar-brand me-0 ms-3" href="<?= "/report" ?>"><i class="fal fa-analytics"></i>&nbsp;Insights Dashboard</a>
        <?php
        endif;
        ?>
        <a href="/admin" class="btn btn-warning ms-auto">Admin Panel</a>
</nav>