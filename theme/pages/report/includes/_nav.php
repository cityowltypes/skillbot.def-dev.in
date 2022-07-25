<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom sticky-top">
    <div class="container-fluid justify-content-start">
        <?php if (isset($_GET['handle']) && ($_SESSION['role_slug'] ?? null) === 'admin'): ?>
        <button id="toggle" class="hamburger ms-lg-2 px-0" data-expanded="false"><span></span></button>
        <?php endif; ?>

        <a
                class="navbar-brand me-0 ms-3"
                href="<?= "https://t.me/{$_GET['handle']}" ?>"
                target="_blank">
            <i class="fab fa-telegram-plane me-2"></i><?=$_GET['handle']?>
        </a>

        <?php if (($_SESSION['role_slug'] ?? null) === 'admin'): ?>
        <a href="/admin" class="btn btn-warning ms-auto">Admin Panel</a>
        <?php endif; ?>
</nav>