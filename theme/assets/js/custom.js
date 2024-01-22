'use strict';

// handle analytics filter
(() => {
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
    if (!anArray) return [];

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
