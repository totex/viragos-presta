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
        }
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

    }

    function init() {
        transformDeliveryCityFields();

        document.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('js-shipping-village-select')) {
                handlePostalCodeAutoFill(e.target);
            }
        });

        if (typeof window.prestashop !== 'undefined' && window.prestashop.on) {
            window.prestashop.on('updatedAddressForm', function () {
                setTimeout(function () {
                    transformDeliveryCityFields();
                }, 50);
            });
            window.prestashop.on('updateCart', function () {
                setTimeout(function () {
                    transformDeliveryCityFields();
                }, 50);
            });
            window.prestashop.on('changedCheckoutStep', function () {
                setTimeout(function () {
                    transformDeliveryCityFields();
                }, 50);
            });
        }

        // Observe dynamic DOM changes for address step updates
        if (typeof MutationObserver !== 'undefined') {
            var observer = new MutationObserver(function (mutations) {
                var shouldTransform = false;
                for (var i = 0; i < mutations.length; i++) {
                    var addedNodes = mutations[i].addedNodes;
                    for (var j = 0; j < addedNodes.length; j++) {
                        var node = addedNodes[j];
                        if (node.nodeType !== 1) {
                            continue;
                        }

                        if ((node.matches && node.matches('input[name="city"]'))
                            || (node.querySelector && node.querySelector('input[name="city"]'))
                        ) {
                            shouldTransform = true;
                            break;
                        }
                    }
                    if (shouldTransform) {
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
