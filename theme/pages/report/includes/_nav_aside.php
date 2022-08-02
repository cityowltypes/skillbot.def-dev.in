<aside class="side-nav border-end shadow">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a
                href="/report"
                class="w-100 btn text-white rounded-0 text-start text-decoration-none">
                <i class="fad fa-arrow-left me-2"></i>Go back
            </a>
        </li>
        <li class="nav-item">
            <a
                    href="<?= "/report/chatbot?id={$_GET['id']}&handle={$_GET['handle']}" ?>"
                    class="w-100 btn text-white rounded-0 text-start text-decoration-none">
                <i class="fad fa-redo me-2"></i>Reset & Reload
            </a>
        </li>
        <li class="nav-item">
            <a
                href="/report/user-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>"
                class="w-100 btn text-white rounded-0 text-start text-decoration-none">
                <i class="fad fa-download me-2"></i>Download
            </a>
        </li>
    </ul>
</aside>
