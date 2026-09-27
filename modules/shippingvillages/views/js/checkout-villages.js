/**
 * 2007-2026 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2026 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

(function () {
    'use strict';

    function getVillagesList() {
        if (typeof window.shippingVillagesList !== 'undefined' && Array.isArray(window.shippingVillagesList)) {
            return window.shippingVillagesList;
        }
        return [];
    }

    function getCurrencyConfig() {
        if (typeof window.shippingVillagesCurrency !== 'undefined' && window.shippingVillagesCurrency) {
            return window.shippingVillagesCurrency;
        }
        return {
            sign: '€',
            iso_code: 'EUR',
            format: '€%s',
            precision: 2
        };
    }

    function getFreeText() {
        return (typeof window.shippingVillagesFreeText === 'string' && window.shippingVillagesFreeText)
            ? window.shippingVillagesFreeText
            : 'Free';
    }

    /**
     * Parse numeric float amount from price text (e.g. "€35.67", "35,67 €", "Free")
     */
    function parsePriceAmount(text) {
        if (!text) return 0.0;
        var clean = text.trim();
        if (/free|zadarmo|gratuit|kostenlos|gratis/i.test(clean)) {
            return 0.0;
        }
        // Match numbers with either dot or comma as decimal
        // Remove spaces and non-numeric chars except . and ,
        var match = clean.match(/[\d\s.,]+/);
        if (!match) return 0.0;

        var numStr = match[0].replace(/\s/g, '');
        // If both comma and dot exist (e.g. 1,234.56 or 1.234,56)
        if (numStr.indexOf(',') !== -1 && numStr.indexOf('.') !== -1) {
            if (numStr.indexOf(',') < numStr.indexOf('.')) {
                numStr = numStr.replace(/,/g, '');
            } else {
                numStr = numStr.replace(/\./g, '').replace(',', '.');
            }
        } else if (numStr.indexOf(',') !== -1) {
            numStr = numStr.replace(',', '.');
        }

        var val = parseFloat(numStr);
        return isNaN(val) ? 0.0 : val;
    }

    /**
     * Format a numeric price based on currency configuration & sample DOM element
     */
    function formatPrice(amount) {
        var currency = getCurrencyConfig();
        var precision = typeof currency.precision !== 'undefined' ? parseInt(currency.precision, 10) : 2;
        var sign = currency.sign || '€';

        // Detect currency symbol position and decimal separator from on-page elements
        var isPrefix = true;
        var decimalSep = '.';
        var sampleEl = document.querySelector(
            '#cart-subtotal-products .cart-summary__value, ' +
            '#cart-subtotal-products .value, ' +
            '.js-cart-summary-totals .cart-summary__value, ' +
            '.js-cart-summary-totals .value'
        );

        if (sampleEl && sampleEl.textContent) {
            var sampleText = sampleEl.textContent.trim();
            if (sampleText.endsWith(sign)) {
                isPrefix = false;
            }
            if (sampleText.indexOf(',') !== -1 && sampleText.indexOf('.') === -1) {
                decimalSep = ',';
            }
        }

        var formattedNumber = Number(amount).toFixed(precision);
        if (decimalSep === ',') {
            formattedNumber = formattedNumber.replace('.', ',');
        }

        if (isPrefix) {
            return sign + formattedNumber;
        }
        return formattedNumber + ' ' + sign;
    }

    /**
     * Handle postal code auto-fill when selecting a village
     */
    function handlePostalCodeAutoFill(target) {
        if (!target) return;
        var select = target.closest ? target.closest('.js-shipping-village-select') : target;
        if (!select || !select.options || select.selectedIndex < 0) return;

        var selectedOption = select.options[select.selectedIndex];
        if (!selectedOption) return;

        var zipcode = selectedOption.getAttribute('data-zipcode');
        if (!zipcode) return;

        var form = select.closest('form');
        if (!form) return;

        var postcodeInput = form.querySelector('input[name="postcode"]');
        if (postcodeInput) {
            postcodeInput.value = zipcode;
            postcodeInput.setAttribute('data-autofilled-by-village', 'true');
            postcodeInput.dispatchEvent(new Event('change', { bubbles: true }));
            postcodeInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }

    /**
     * Dynamically calculate and update shipping and total in checkout summary
     */
    function handleShippingPriceUpdate(target) {
        var select = target ? (target.closest ? target.closest('.js-shipping-village-select') : target) : document.querySelector('.js-shipping-village-select');
        if (!select || !select.options || select.selectedIndex < 0) return;

        var selectedOption = select.options[select.selectedIndex];
        if (!selectedOption || selectedOption.disabled) {
            return;
        }

        var priceAttr = selectedOption.getAttribute('data-price');
        var villagePrice = 0.0;
        if (priceAttr !== null && priceAttr !== '') {
            villagePrice = parseFloat(priceAttr);
            if (isNaN(villagePrice)) villagePrice = 0.0;
        } else {
            var cityName = selectedOption.value ? selectedOption.value.trim().toLowerCase() : '';
            var villages = getVillagesList();
            for (var i = 0; i < villages.length; i++) {
                if (villages[i].name && villages[i].name.toLowerCase() === cityName) {
                    villagePrice = parseFloat(villages[i].price) || 0.0;
                    break;
                }
            }
        }

        // 1. Update Shipping Subtotal
        var shippingSubtotalLine = document.getElementById('cart-subtotal-shipping');
        if (shippingSubtotalLine) {
            var shippingValueEl = shippingSubtotalLine.querySelector('.cart-summary__value, .value');
            if (shippingValueEl) {
                if (!shippingValueEl.hasAttribute('data-base-value')) {
                    var initialShippingAmount = parsePriceAmount(shippingValueEl.textContent);
                    shippingValueEl.setAttribute('data-base-value', initialShippingAmount);
                    shippingValueEl.setAttribute('data-base-text', shippingValueEl.textContent.trim());
                }

                var baseShipping = parseFloat(shippingValueEl.getAttribute('data-base-value')) || 0.0;
                var baseShippingText = shippingValueEl.getAttribute('data-base-text') || getFreeText();
                var currentShipping = baseShipping + villagePrice;

                if (currentShipping > 0) {
                    shippingValueEl.textContent = formatPrice(currentShipping);
                } else {
                    shippingValueEl.textContent = baseShippingText;
                }
            }
        }

        // 2. Update Total Lines (Totals Container)
        var totalContainers = document.querySelectorAll('.js-cart-summary-totals, .cart-summary-totals, .cart-summary__total');
        totalContainers.forEach(function (container) {
            var totalLines = container.querySelectorAll('.cart-summary__line, .cart-summary-line, .cart-total');
            totalLines.forEach(function (line) {
                // Skip tax subtotal line if inside totals
                if (line.id === 'cart-subtotal-tax') return;

                var totalValueEl = line.querySelector('.cart-summary__value, .value');
                if (totalValueEl) {
                    if (!totalValueEl.hasAttribute('data-base-value')) {
                        var initialTotal = parsePriceAmount(totalValueEl.textContent);
                        totalValueEl.setAttribute('data-base-value', initialTotal);
                        totalValueEl.setAttribute('data-base-text', totalValueEl.textContent.trim());
                    }

                    var baseTotal = parseFloat(totalValueEl.getAttribute('data-base-value')) || 0.0;
                    var newTotal = baseTotal + villagePrice;
                    totalValueEl.textContent = formatPrice(newTotal);
                }
            });
        });
    }

    /**
     * Transform text input to styled select dropdown
     */
    function transformDeliveryCityFields() {
        var villages = getVillagesList();
        if (!villages || !villages.length) {
            return;
        }

        // Target delivery forms (exclude explicit invoice address containers)
        var deliveryContainers = document.querySelectorAll('#delivery-address, [data-type="delivery"], #checkout-addresses-step, .js-address-form');
        deliveryContainers.forEach(function (container) {
            if (container.closest('#invoice-address') || container.closest('#invoice-addresses')) {
                return;
            }

            var cityInputs = container.querySelectorAll('input[name="city"]:not(.js-shipping-village-select)');
            cityInputs.forEach(function (input) {
                if (input.closest('#invoice-address') || input.closest('#invoice-addresses')) {
                    return;
                }

                var currentVal = input.value ? input.value.trim() : '';
                var select = document.createElement('select');
                select.name = 'city';
                select.id = input.id || 'field-city';
                select.className = (input.className || '') + ' form-select form-control form-control-select js-shipping-village-select';
                select.required = input.required;

                var defaultOption = document.createElement('option');
                defaultOption.value = '';
                defaultOption.textContent = '-- please choose a delivery village --';
                defaultOption.disabled = true;
                if (!currentVal) {
                    defaultOption.selected = true;
                }
                select.appendChild(defaultOption);

                villages.forEach(function (village) {
                    var option = document.createElement('option');
                    option.value = village.name;
                    var priceVal = (typeof village.price !== 'undefined' && village.price !== null) ? parseFloat(village.price) : 0;
                    if (isNaN(priceVal)) {
                        priceVal = 0;
                    }
                    var currencyIso = (window.shippingVillagesCurrency && window.shippingVillagesCurrency.iso_code) ? window.shippingVillagesCurrency.iso_code : 'EUR';

                    option.setAttribute('data-price', priceVal);
                    if (village.zipcode) {
                        option.setAttribute('data-zipcode', village.zipcode);
                        option.textContent = village.name + ' (' + village.zipcode + ') - ' + priceVal + ' ' + currencyIso;
                    } else {
                        option.textContent = village.name + ' - ' + priceVal + ' ' + currencyIso;
                    }

                    if (currentVal && village.name.toLowerCase() === currentVal.toLowerCase()) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });

                input.parentNode.replaceChild(select, input);
            });
        });

        // Trigger dynamic summary price sync if a village is already selected
        var activeSelect = document.querySelector('.js-shipping-village-select');
        if (activeSelect && activeSelect.value) {
            handleShippingPriceUpdate(activeSelect);
        }
    }

    function init() {
        transformDeliveryCityFields();

        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('js-shipping-village-select')) {
                handlePostalCodeAutoFill(e.target);
                handleShippingPriceUpdate(e.target);
            }
        });

        if (typeof window.prestashop !== 'undefined' && window.prestashop.on) {
            window.prestashop.on('updatedAddressForm', function () {
                setTimeout(function () {
                    transformDeliveryCityFields();
                    var activeSelect = document.querySelector('.js-shipping-village-select');
                    if (activeSelect && activeSelect.value) {
                        handleShippingPriceUpdate(activeSelect);
                    }
                }, 50);
            });
            window.prestashop.on('updateCart', function () {
                setTimeout(function () {
                    transformDeliveryCityFields();
                    var activeSelect = document.querySelector('.js-shipping-village-select');
                    if (activeSelect && activeSelect.value) {
                        handleShippingPriceUpdate(activeSelect);
                    }
                }, 50);
            });
            window.prestashop.on('changedCheckoutStep', function () {
                setTimeout(function () {
                    transformDeliveryCityFields();
                    var activeSelect = document.querySelector('.js-shipping-village-select');
                    if (activeSelect && activeSelect.value) {
                        handleShippingPriceUpdate(activeSelect);
                    }
                }, 50);
            });
        }

        // Observe dynamic DOM changes for address step updates
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function (mutations) {
                var shouldTransform = false;
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].addedNodes.length > 0) {
                        shouldTransform = true;
                        break;
                    }
                }
                if (shouldTransform) {
                    transformDeliveryCityFields();
                }
            });

            var target = document.getElementById('checkout-addresses-step') || document.body;
            if (target) {
                observer.observe(target, { childList: true, subtree: true });
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function () {
            init();
        });
    }
})();
