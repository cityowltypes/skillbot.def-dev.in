'use strict';

// handle analytics filter
(() => {
    let district = document.querySelector('select[name="district"]');
    if (district) {
        district.addEventListener('change', (e) => {
            e.preventDefault();

            let search = window.location.search;
            search = new URLSearchParams(search);

            if (district.value === 'all') {
                search.delete('district');
            }
            else {
                search.set('district', district.value);
            }
            search = search.toString();
            console.log(search)

            window.location.href = `${window.location.pathname}?${search}`;
        });
    }

    let state = document.querySelector('select[name="state"]');
    if (state) {
        state.addEventListener('change', (e) => {
            e.preventDefault();

            let search = window.location.search;
            search = new URLSearchParams(search);

            if (state.value === 'all') {
                search.delete('state');
            }
            else {
                search.set('state', state.value);
            }

            search.delete('district');
            search = search.toString();

            window.location.href = `${window.location.pathname}?${search}`;
        })
    }
})();

// draw charts for analytics
(() => {
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
            autocolors,
            ChartDataLabels
        ]
    });

    // chart for users by category
    new Chart(document.querySelector('canvas#users_per_category'), {
        type: 'bar',
        data: {
            labels: getColumn(analytics_data['users_per_category'], 'category'),
            datasets: [{
                label: '',
                data: getColumn(analytics_data['users_per_category'], 'count')
            }],
        },
        options: {
            plugins: {
                autocolors: {
                    mode: getColumn(analytics_data['users_per_category'], 'count')
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

    // users per sex
    new Chart(document.querySelector('canvas#users_per_sex'), {
        type: 'pie',
        data: {
            labels: getColumn(analytics_data['users_per_gender'], 'sex'),
            datasets: [{
                label: '',
                data: getColumn(analytics_data['users_per_gender'], 'count')
            }],
        },
        options: {
            plugins: {
                autocolors: {
                    mode: getColumn(analytics_data['users_per_gender'], 'count')
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
            autocolors,
            ChartDataLabels
        ]
    });
})();

function isInt (value) {
    return !isNaN(value) &&
        parseInt(Number(value)) == value &&
        !isNaN(parseInt(value, 10));
}

function numberFormatter (value, decimal) {
    return parseFloat(parseFloat(value).toFixed(decimal)).toLocaleString(
      "en-IN",
      {
        useGrouping: true,
      }
    );
};

function getColumn (anArray, columnNumber) {
    if (typeof anArray === 'object') {
        anArray = Object.values(anArray);
    }

    try {
        return anArray.map(function(row) {
            if (typeof row[columnNumber] === 'string') {
                return row[columnNumber][0].toUpperCase() + row[columnNumber].substring(1);
            }

            return row[columnNumber];
        });
    }
    catch (e) {
        return anArray;
    }
}