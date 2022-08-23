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

    updateUrl(`/report/chatbot?${searchParams.toString()}`);
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
        let filterForm = document.querySelector('#region_filter');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                filterFormSubmission(e);
            });
        }
    }

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

        let anchor = document.querySelector('#responses_table_link');
        if (anchor) {
            anchor.href = `${anchor.dataset.href}${window.location.search}`;
        }
    }
}

function drawAnalyticsCharts() {
    if (!document.querySelector('input[name="is_analytics"]')) {
        return;
    }

    const autocolors = window['chartjs-plugin-autocolors'];

    let chart = null;

    // users by district
    chart = document.querySelector('canvas#users_by_district');
    if (chart) {
        new Chart(chart, {
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
    chart = document.querySelector('canvas#users_by_age');
    if (chart) {
        new Chart(chart, {
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
    }

    // users per module
    chart = document.querySelector('canvas#users_per_module');
    if (chart) {
        new Chart(chart, {
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
                            return `${numberFormatter(value)}`;
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
    }

    // chart for users by category
    chart = document.querySelector('canvas#users_per_category');
    if (chart) {
        new Chart(chart, {
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
    }

    // users per sex
    chart = document.querySelector('canvas#users_per_sex');
    if (chart) {
        new Chart(chart, {
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

        if (analyticsContainer) {
            analyticsContainer.innerHTML = res;
        }

        drawAnalyticsCharts();
    });
}

async function filterFormSubmission(e) {
    e.preventDefault();
    let container = document.querySelector('#analytics-container');
    if (!container) {
        return;
    }

    container.innerHTML = loading;

    let form = new FormData(e.target);
    let today = new Date().toISOString().slice(0, 10);
    let dates = {
        start: form.get('start_date') ?? today,
        end: form.get('end_date') ?? today
    };

    let search = String(window.location.search);
    search = new URLSearchParams(search);

    search.set('start_date', dates.start);
    search.set('end_date', dates.end);
    updateUrl(`${window.location.pathname}?${search.toString()}`);

    search.set('no_filter', 'true');

    let res = await fetch(`/report/analytics?${search.toString()}`);
    res = await res.text();

    container.innerHTML = res;
    drawAnalyticsCharts();
}

// #responses_by_date runs on analytics dashboard
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

let responsesTable = document.querySelector('#responses-table');
if (responsesTable) {
    let tableHeads = responsesTable.querySelectorAll('thead th.sortable');
    if (tableHeads) {
        tableHeads.forEach(th => {
            th.addEventListener('click', (e) => {
                e.preventDefault();

                let th = e.target.closest('th');
                let searchParam = window.location.search;
                let usp = new URLSearchParams(searchParam);

                usp.set('sort', th.dataset.sort);
                usp.delete('page');

                if (!!th.dataset.order && th.dataset.order !== '') {
                    usp.set('order', th.dataset.order);
                }
                else {
                    usp.set('order', 'desc');
                }

                location.replace(`${window.location.pathname}?${usp.toString()}`);
            });
        })
    }
}

let searchForm = document.querySelector('#search_form');
if (searchForm) {
    searchForm.addEventListener('submit', (e) => {
        e.preventDefault();

        let fd = new FormData(e.target);
        let search = fd.get('search').toString();
        search = encodeURIComponent(search);

        let usp = new URLSearchParams(window.location.search);
        usp.delete('page');
        usp.set('order', 'desc');
        usp.set('sort', 'id');

        if (!search) {
            usp.delete('search');
        }
        else {
            usp.set('search', search);
        }

        location.replace(`${window.location.pathname}?${usp.toString()}`);
    })
}

let exportBtn = document.querySelector('#export-table');
if (exportBtn) {
    exportBtn.addEventListener('click', (e) => {
        e.preventDefault();

        let usp = new URLSearchParams(window.location.search);
        usp.set('sort', 'id');
        usp.set('order', 'desc');
        usp.delete('page');
        usp.set('export', 'true');

        window.open(`${window.location.pathname}?${usp.toString()}`, '_blank');
    });
}

let editFormModal = document.querySelector('#edit-form-modal');
if (editFormModal) {
    let form = editFormModal.querySelector('form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            let fd = new FormData(form);
            let res = await fetch(`/theme/api/response?id=${fd.get('id')}&chatbot=${fd.get('chatbot_id')}`, {
                method: 'post',
                body: fd
            });

            res = await res.json();
            console.log(res);

            for (let key in res) {
                if (!res.hasOwnProperty(key)) {
                    continue;
                }

                let td = document.querySelector(`td[data-name='${key}_${res.id}']`);
                if (!td) continue;

                td.innerText = res[key];
            }

            let badge = form.querySelector('.badge');
            badge.classList.remove('d-none');

            setTimeout(() => {
                badge.classList.add('d-none');
            }, 5000)
        });
    }

    editFormModal = new bootstrap.Modal(editFormModal, {
        backdrop: 'static',
        focus: true,
        keyboard: false
    });
}

let tableEditBtn = document.querySelectorAll('td > button.edit-form');
if (tableEditBtn) {
    tableEditBtn.forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();

            let btn = e.target.closest('button');
            if (editFormModal) {
                let usp = new URLSearchParams(window.location.search);
                let endpoint = `/theme/api/response?id=${btn.dataset.id}&chatbot=${usp.get('id')}`;
                try {
                    let res = await fetch(endpoint, {
                        method: 'get'
                    })

                    if (res.status >= 400 && res.status < 600) {
                        throw new Error (res.statusText);
                    }

                    res = await res.json();

                    // reset form's last values
                    let form = document.querySelector('#edit-form-modal form');
                    if (form) form.reset();

                    let modal = document.querySelector('#edit-form-modal');
                    if (modal) {
                        modal = modal.querySelector('.modal-title');
                        modal.innerText = res.id;
                    }

                    for (let key in res) {
                        if (res.hasOwnProperty(key)) {
                            let form = document.querySelector(`input#${key}`);
                            if (!form) continue;

                            form.value = res[key];
                        }
                    }

                    editFormModal.show();
                } catch (e) {
                    console.error(e);
                }
            }
        })
    })
}
