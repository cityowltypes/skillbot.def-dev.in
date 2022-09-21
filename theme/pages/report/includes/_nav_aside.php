<?php
/**
 * @var string $role_slug
 */
?>
<aside class="side-nav border-end shadow">
    <ul class="nav flex-column">
        <?php
        if ($role_slug === 'admin'):
        ?>
        <li class="nav-item">
            <a
                href="/report"
                class="w-100 btn text-white rounded-0 text-start text-decoration-none">
                <i class="fad fa-arrow-left me-2"></i>Go back
            </a>
        </li>
        <?php
        endif;
        ?>
        <li class="nav-item">
            <a
                    href="<?= "/report/export?id={$_GET['id']}&handle={$_GET['handle']}" ?>"
                class="w-100 btn text-white rounded-0 text-start text-decoration-none">
                <i class="fad fa-download me-2"></i>Download
            </a>
        </li>
        <li class="nav-item">
            <a
                    href="<?= "/report/export?id={$_GET['id']}&handle={$_GET['handle']}&target=unfiltered" ?>"
                    class="w-100 btn text-white rounded-0 text-start text-decoration-none">
                <i class="fad fa-download me-2"></i>Download Raw
            </a>
        </li>
    </ul>
</aside>
