'use strict';

let analytics_data = null;
let aside = document.querySelector('aside.side-nav');
let toggle = document.querySelector('#toggle');
let loading = "<div class='lds-facebook'><div></div><div></div><div></div></div>";
let district = null;

if (typeof valid_map_keys !== 'undefined') {
    let mapGroups = document.querySelectorAll(".map g.map_of_india_svg");
    if (mapGroups) {
        mapGroups.forEach(g => {
            // if map has valid keys
            if (!valid_map_keys.includes(g.dataset.state)) {
                g.classList.add('inactive');
                g.classList.remove('map_of_india_svg');
            }
        });
    }
}

if (toggle) {
    toggle.addEventListener('click', (e) => toggleSideBar(e));
}

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

selectMapRegion(null, true);

// restore sidebar state
if (aside && typeof localStorage.getItem('sidebarExpanded')) {
    let button = document.querySelector('#toggle');

    if (localStorage.getItem('sidebarExpanded') === 'true' && button) {
        aside.classList.add('expanded');
        button.dataset.expanded = 'true';
    }
    else {
        aside.classList.remove('expanded');
        button.dataset.expanded = 'false';
    }
}

function getWidth() {
    return Math.max(
        document.body.scrollWidth,
        document.documentElement.scrollWidth,
        document.body.offsetWidth,
        document.documentElement.offsetWidth,
        document.documentElement.clientWidth
    );
}

function getHeight() {
    return Math.max(
        document.body.scrollHeight,
        document.documentElement.scrollHeight,
        document.body.offsetHeight,
        document.documentElement.offsetHeight,
        document.documentElement.clientHeight
    );
}

function toggleSideBar(e) {
    e.preventDefault();

    if (!aside) return;

    if (e.target.dataset.expanded === 'true') {
        aside.classList.remove('expanded');
        e.target.dataset.expanded = 'false';
        localStorage.setItem('sidebarExpanded', 'false');
    }
    else {
        aside.classList.add('expanded');
        e.target.dataset.expanded = 'true';
        localStorage.setItem('sidebarExpanded', 'true');
    }
}

async function selectMapRegion(g, init = false) {
    let uiTotalUsers = document.querySelector('#totalUsers');
    let uiAverageAge = document.querySelector('#averageAge');
    let uiStateName = document.querySelector('#stateName');
    let showButton = document.querySelector('#showDetailedStats');
    let detailedAnalytics = document.querySelector('#detailed-analytics');

    if (!document.querySelector('svg.map_of_india')) {
        return;
    }

    if (detailedAnalytics) {
        detailedAnalytics.innerHTML = loading;
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

    if (!init) {
        g.classList.add('active');
    }

    let searchParams = String(window.location.search);
    searchParams = new URLSearchParams(searchParams);

    let state = null;
    if (!g && searchParams.get('state')) {
        state = document.querySelector(`g[data-state="${searchParams.get('state')}"]`);
        if (state) {
            state.classList.add('active');
        }
    }

    if (uiStateName && !init) {
        uiStateName.innerText = g.dataset.state.replace(/_/g, " ");
    }
    else if (uiStateName && state) {
        uiStateName.innerText = searchParams.get('state').replace(/_/g, ' ');
    }

    district = searchParams.get('district');
    searchParams.delete('district');

    if (!init) {
        searchParams.set('state', g.dataset.state);
    }
    searchParams.set('interface', 'api');

    let requestUrl = `/report/analytics?${searchParams.toString()}`;
    let res = await fetch(requestUrl);
    res = await res.json();
    analytics_data = res;


    searchParams.delete('interface');

    if (uiTotalUsers) {
        if (res.state !== "" || init) {
            uiTotalUsers.innerText = numeral(res['user_count'] ?? 0).format('0,0');
        }
        else {
            uiTotalUsers.innerText = 0;
        }
    }

    if (uiAverageAge) {
        if (res.state !== "" || init) {
            uiAverageAge.innerText = res['average_age'] ?? 0;
        }
        else {
            uiAverageAge.innerText = 0;
        }
    }

    if (init && district) {
        searchParams.set('district', district);
    }

    requestUrl = `/report/analytics?${searchParams.toString()}`;

    let detailedDataHtml = await fetch(requestUrl);
    detailedDataHtml = await detailedDataHtml.text();

    if (detailedAnalytics) {
        detailedAnalytics.innerHTML = detailedDataHtml;
        drawAnalyticsCharts();
        districtFilter();
    }

    updateUrl(`/report/chatbot?${searchParams.toString()}`);

    if (
        showButton &&
        (res.state !== "") &&
        !!res.encodedState
    ) {
        requestUrl = `/report/analytics?${searchParams.toString()}`;

        showButton.href = requestUrl;

        showButton.classList.remove('d-none');
    }
}

function updateUrl (url) {
    if (typeof url === 'string') {
        window.history.replaceState({}, "", url);
    }
}

function drawAnalyticsCharts() {
    if (!document.querySelector('input[name="is_analytics"]')) {
        return;
    }

    const autocolors = window['chartjs-plugin-autocolors'];

    // users by district
    let usersByDistrict = document.querySelector('canvas#users_by_district');
    if (usersByDistrict) {
        new Chart(usersByDistrict, {
            type: 'bar',
            data: {
                labels: getColumn(analytics_data['users_by_district'], 'district'),
                datasets: [{
                    label: '',
                    data: getColumn(analytics_data['users_by_district'], 'count')
                }],
            },
            options: {
                plugins: {
                    autocolors: {
                        mode: getColumn(analytics_data['users_by_district'], 'count')
                    },
                    tooltip: {
                        enabled: true
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        offset: 8,
                        formatter: function (value, context) {
                            return numberFormatter(value);
                        },
                    },
                    legend: {
                        display: false
                    }
                },
                indexAxis: 'x',
                skipNull: true,
                minBarLength: 12,
                maxBarThickness: 40
            },
            plugins: [
                autocolors,
                ChartDataLabels
            ]
        });
    }

    // chart for users by age
    new Chart(document.querySelector('canvas#users_by_age'), {
        type: 'bar',
        data: {
            labels: getColumn(analytics_data['users_by_age'], 'age'),
            datasets: [{
                label: '',
                data: getColumn(analytics_data['users_by_age'], 'age_count')
            }],
        },
        options: {
            plugins: {
                autocolors: {
                    mode: getColumn(analytics_data['users_by_age'], 'age_count')
                },
                tooltip: {
                    enabled: true
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    offset: 8,
                    formatter: function (value, context) {
                        return numberFormatter(value);
                    },
                },
                legend: {
                    display: false
                }
            },
            indexAxis: 'y',
            skipNull: true,
            minBarLength: 12,
            maxBarThickness: 40
        },
        plugins: [
            autocolors,
            ChartDataLabels
        ]
    });

    // users per module
    new Chart(document.querySelector('canvas#users_per_module'), {
        type: 'pie',
        data: {
            labels: getColumn(analytics_data['per_module_users'], 'module_name'),
            datasets: [{
                label: '',
                data: getColumn(analytics_data['per_module_users'], 'count')
            }],
        },
        options: {
            plugins: {
                autocolors: {
                    mode: getColumn(analytics_data['per_module_users'], 'count')
                },
                tooltip: {
                    enabled: true
                },
                datalabels: {
                    anchor: 'center',
                    color: 'black',
                    font: {
                        weight: 'normal'
                    },
                    formatter: function (value, context) {
                        return `${numberFormatter(value)}\n${context.chart.data.labels[context.dataIndex].replace(/\s/, '\n')}`;
                    },
                    textAlign: 'center'
                }
            },
            indexAxis: 'y',
            skipNull: true,
            minBarLength: 12
        },
        plugins: [
            autocolors,
            ChartDataLabels
        ]
    });

    // chart for users by category
    new Chart(document.querySelector('canvas#users_per_category'), {
        type: 'bar',
        data: {
            labels: analytics_data['users_per_category']['labels'],
            datasets: [
                {
                    label: 'Male',
                    data: analytics_data['users_per_category']['male'],
                    backgroundColor: '#36a2eb'
                },
                {
                    label: 'Female',
                    data: analytics_data['users_per_category']['female'],
                    backgroundColor: '#ff6384',
                },
            ],
        },
        options: {
            plugins: {
                tooltip: {
                    enabled: true
                },
                datalabels: {
                    anchor: 'end',
                    align: 'end',
                    offset: 8,
                    formatter: function (value, context) {
                        return numberFormatter(value);
                    },
                },
                legend: {
                    display: true
                },
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                }
            },
            indexAxis: 'x',
            skipNull: true,
            minBarLength: 0,
            maxBarThickness: 40
        },
        plugins: [
            ChartDataLabels
        ]
    });

    // users per sex
    new Chart(document.querySelector('canvas#users_per_sex'), {
        type: 'pie',
        data: {
            labels: getColumn(analytics_data['users_per_gender'], 'sex'),
            datasets: [{
                label: '',
                data: getColumn(analytics_data['users_per_gender'], 'count'),
                backgroundColor: [
                    analytics_data['users_per_gender'][0].sex === 'female' ? '#ff6384' : '#36a2eb',
                    analytics_data['users_per_gender'][0].sex === 'female' ? '#36a2eb' : '#ff6384'
                ]
            }],
        },
        options: {
            plugins: {
                tooltip: {
                    enabled: true
                },
                datalabels: {
                    anchor: 'center',
                    color: 'black',
                    font: {
                        weight: 'normal'
                    },
                    formatter: function (value, context) {
                        return `${numberFormatter(value)}\n(${context.chart.data.labels[context.dataIndex]})`;
                    },
                    textAlign: 'center'
                }
            },
            indexAxis: 'y',
            skipNull: true,
            minBarLength: 12
        },
        plugins: [
            ChartDataLabels
        ]
    });
}

function districtFilter() {
    let district = document.querySelector('select[name="district"]');
    if (!district) return;

    district.addEventListener('change', async (e) => {
        e.preventDefault();

        let analyticsContainer = document.querySelector('#analytics-container');
        if (analyticsContainer) analyticsContainer.innerHTML = loading;

        let search = window.location.search;
        search = new URLSearchParams(search);

        if (district.value === 'all') {
            search.delete('district');
        }
        else {
            search.set('district', district.value);
        }

        updateUrl(`/report/chatbot?${search.toString()}`);

        search.set('no_filter', 'true');
        search.set('interface', 'api');

        let res = await fetch(`/report/analytics?${search.toString()}`);
        res = await res.json();
        analytics_data = res;

        search.delete('interface');

        res = await fetch(`/report/analytics?${search.toString()}`);
        res = await res.text();

        if (analyticsContainer) analyticsContainer.innerHTML = res;

        drawAnalyticsCharts();
    });
}

let responsesByDate = document.querySelector('#responses_by_date');
if (responsesByDate) {
    new Chart(responsesByDate, {
        type: 'line',
        data: {
            labels: TRAFFIC.date.slice(-14),
            datasets: [
                {
                    label: '',
                    data: TRAFFIC.count.slice(-14),
                    backgroundColor: '#b43232',
                    borderColor: '#b43232',
                }
            ],
        },
        options: {
            plugins: {
                tooltip: {
                    enabled: true
                },
                legend: {
                    display: false
                },
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                }
            },
            indexAxis: 'x',
            skipNull: true,
            minBarLength: 0,
            maxBarThickness: 40
        }
    });
}