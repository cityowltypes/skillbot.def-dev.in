<?php
/**
 * @var string $role_slug
 * @var array $allowed_roles
 * @var array $allowed_chatbots
 */
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom sticky-top">
    <div class="container-fluid justify-content-start">
        <?php
        if (
                isset($_GET['handle']) &&
                in_array($role_slug, $allowed_roles) &&
                in_array($_GET['handle'] ?? '', $allowed_chatbots)
        ) {
            echo "<button id='toggle' class='hamburger ms-lg-2 px-0' data-expanded='false'><span></span></button>";
        }
        ?>

        <?php if (isset($_GET['handle'])): ?>
        <a
                class="navbar-brand me-0 ms-3"
                href="<?= "https://t.me/{$_GET['handle']}" ?>"
                target="_blank">
            @<?=$_GET['handle']?>
        </a>
        <?php endif; ?>

        <?php if (($_SESSION['role_slug'] ?? null) === 'admin'): ?>
        <a href="/admin" target="_blank" class="btn btn-light ms-auto">Admin Panel</a>
        <?php endif; ?>
</nav>
