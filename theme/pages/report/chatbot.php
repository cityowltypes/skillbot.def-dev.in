<?php include_once __DIR__ . '/../_header.php'?>

<div class="container py-5">
	<h1 class="fw-light text-center mb-3">Insights for <a target="new" href="https://t.me/<?=$_GET['handle']?>" class="fw-bold text-success">@<?=$_GET['handle']?></a></h1>
    <div class="row">
        <div class="col-lg-5 mx-auto">
            <a href="/report/user-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>" class="w-100 d-block btn btn-primary my-2 btn-lg"><i class="fad fa-download"></i>&nbsp;User Data</a>
            <a href="/report/pre-assessment-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>" class="w-100 d-block btn btn-primary my-2 btn-lg"><i class="fad fa-download"></i>&nbsp;Pre-assessment Data</a>
            <a href="/report/post-assessment-data?id=<?= $_GET['id'] ?>&handle=<?=$_GET['handle']?>" class="w-100 d-block btn btn-primary my-2 btn-lg"><i class="fad fa-download"></i>&nbsp;Post-assessment Data</a>
            <a href="/report" class="w-100 d-block btn btn-warning my-2 btn-lg">&larr;&nbsp;Go back</a>
        </div>
    </div>
</div>


<div class="container py-5">
	<div class="row">
		<div class='col-lg-8 map mx-auto'>
			<?php
			$map = file_get_contents(THEME_PATH . "/assets/img/india.svg");
			echo $map;
			?>
		</div>
        <div id="statDisplay" class="col-lg-4 d-flex flex-column justify-content-center align-items-center d-none">
            <div class="text-center">
                <h1 id="stateName" class="fw-light text-capitalize"></h1>
            </div>

            <div class="text-center mt-5">
                <p id="totalUsers" class="display-1"></p>
                <h2 class="h3 fw-light">Total Users</h2>
            </div>

            <div class="text-center mt-5">
                <p id="averageAge" class="display-1"></p>
                <h2 class="h3 fw-light">Average age</h2>
            </div>

            <div class="text-center mt-5">
                <a id="showDetailedStats" href="#/" target="_blank" class="btn btn-outline-primary border-2 pb-2 rounded-pill text-uppercase">
                    <span class="small fw-bold">Show More</span> <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
	</div>
</div>

<script>
    // add click event on map paths
    (() => {
        let mapGroups = document.querySelectorAll(".map g.map_of_india_svg")
        if (!mapGroups) return;

        mapGroups.forEach((g) => {
            g.addEventListener('click', async () => {
                await selectMapRegion(g);
            });
        });
    })();

    async function selectMapRegion(g) {
        let uiTotalUsers = document.querySelector('#totalUsers');
        let uiAverageAge = document.querySelector('#averageAge');
        let uiStateName = document.querySelector('#stateName');
        let showButton = document.querySelector('#showDetailedStats');
        let statDisplay = document.querySelector('#statDisplay');

        let loading = "<div class='lds-facebook'><div></div><div></div><div></div></div>";

        if (statDisplay) {
            statDisplay.classList.remove('d-none');
        }

        if (uiStateName) {
            uiStateName.innerText = g.dataset.state.replace(/_/g, " ");
        }

        if (uiTotalUsers) {
            uiTotalUsers.innerHTML = loading;
        }

        if (uiAverageAge) {
            uiAverageAge.innerHTML = loading;
        }

        if (showButton) {
            showButton.classList.add('d-none');
        }

        let lastActive = document.querySelector('.map g.map_of_india_svg.active');
        if (lastActive) {
            lastActive.classList.remove('active');
        }

        g.classList.add('active');

        let searchParams = String(window.location.search);
        searchParams = new URLSearchParams(searchParams);

        let requestParams = new URLSearchParams();
        requestParams.set('chatbot_id', searchParams.get('id'));
        requestParams.set('state_code', g.dataset.state);
        requestParams.set('interface', 'api');

        let requestUrl = `/report/analytics?${requestParams.toString()}`;
        let res = await fetch(requestUrl);
        res = await res.json();

        requestParams.delete('state_code');
        requestParams.delete('interface');


        if (uiTotalUsers) {
            if (res.state !== "") {
                uiTotalUsers.innerText = res['user_count'] ?? 0;
            }
            else {
                uiTotalUsers.innerText = 0;
            }
        }

        if (uiAverageAge) {
            if (res.state !== "") {
                uiAverageAge.innerText = res['average_age'] ?? 0;
            }
            else {
                uiAverageAge.innerText = 0;
            }
        }


        if (
            showButton &&
            (res.state !== "") &&
            !!res.encodedState
        ) {
            requestParams.set('state', res.encodedState);
            requestUrl = `/report/analytics?${requestParams.toString()}`;

            showButton.href = requestUrl;

            showButton.classList.remove('d-none');
        }
    }
</script>

<?php include_once __DIR__ . '/../_footer.php'?>