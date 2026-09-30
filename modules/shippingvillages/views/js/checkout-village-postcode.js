(function () {
    'use strict';

    document.addEventListener('change', function (event) {
        var citySelect = event.target;
        if (!citySelect || !citySelect.matches || !citySelect.matches('select.js-shipping-village-select')) {
            return;
        }

        var selectedOption = citySelect.options[citySelect.selectedIndex];
        var postcodeInput = citySelect.form && citySelect.form.querySelector('input[name="postcode"]');
        var zipcode = selectedOption && selectedOption.getAttribute('data-zipcode');

        if (postcodeInput && zipcode) {
            postcodeInput.value = zipcode;
            postcodeInput.setAttribute('data-autofilled-by-village', 'true');
        }
    });
})();