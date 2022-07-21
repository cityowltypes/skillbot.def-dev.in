<aside class="side-nav border-end shadow">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a
                href="/report"
                class="w-100 btn text-white rounded-0 text-start text-decoration-none"><i class="far fa-arrow-left me-2"></i>Go back
            </a>
        </li>
        <li class="nav-item">
            <a
                href="/report/user-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>"
                class="w-100 btn text-white rounded-0 text-start text-decoration-none">
                <i class="fad fa-download me-2"></i>User Data
            </a>
        </li>
        <li class="nav-item">
            <a
                href="/report/pre-assessment-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>"
                class="w-100 btn text-white rounded-0 text-start text-decoration-none">
                <i class="fad fa-download me-2"></i>Pre-assessment Data
            </a>
        </li>
        <li class="nav-item">
            <a
                href="/report/post-assessment-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>"
                class="w-100 btn text-white rounded-0 text-start text-decoration-none">
                <i class="fad fa-download me-2"></i>Post-assessment Data
            </a>
        </li>
    </ul>
</aside>
