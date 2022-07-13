'use strict';

// draw charts for analytics
(() => {
    if (!document.querySelector('input[name="is_analytics"]')) {
        return;
    }

    const autocolors = window['chartjs-plugin-autocolors'];

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
                    enabled: false
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
            minBarLength: 12
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
                    enabled: false
                },
                datalabels: {
                    anchor: 'center',
                    color: 'black',
                    font: {
                        weight: 'bold'
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
                    enabled: false
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
            minBarLength: 12
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
                    enabled: false
                },
                datalabels: {
                    anchor: 'center',
                    color: 'black',
                    font: {
                        weight: 'bold'
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

    return anArray.map(function(row) {
        if (typeof row[columnNumber] === 'string') {
            return row[columnNumber][0].toUpperCase() + row[columnNumber].substring(1);
        }

        return row[columnNumber];
    });
}