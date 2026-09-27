<?php
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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'shippingvillages/classes/ShippingVillage.php';

class AdminShippingVillagesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'shipping_village';
        $this->className = 'ShippingVillage';
        $this->identifier = 'id_shipping_village';
        $this->lang = false;
        $this->deleted = false;
        $this->explicitSelect = true;
        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';

        parent::__construct();

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->trans('Delete selected', [], 'Admin.Actions'),
                'confirm' => $this->trans('Delete selected items?', [], 'Admin.Notifications.Warning'),
                'icon' => 'icon-trash',
            ],
            'enableSelection' => [
                'text' => $this->trans('Enable selection', [], 'Admin.Actions'),
                'icon' => 'icon-power-off text-success',
            ],
            'disableSelection' => [
                'text' => $this->trans('Disable selection', [], 'Admin.Actions'),
                'icon' => 'icon-power-off text-danger',
            ],
        ];

        $this->fields_list = [
            'id_shipping_village' => [
                'title' => $this->trans('ID', [], 'Admin.Global'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->trans('Village Name', [], 'Modules.Shippingvillages.Admin'),
                'filter_key' => 'a!name',
            ],
            'zipcode' => [
                'title' => $this->trans('Postal Code', [], 'Admin.Global'),
                'align' => 'center',
                'class' => 'fixed-width-sm',
            ],
            'price' => [
                'title' => $this->trans('Shipping Price', [], 'Modules.Shippingvillages.Admin'),
                'type' => 'price',
                'currency' => true,
                'align' => 'text-right',
                'class' => 'fixed-width-sm',
            ],
            'position' => [
                'title' => $this->trans('Position', [], 'Admin.Global'),
                'filter_key' => 'a!position',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'position' => 'position',
            ],
            'active' => [
                'title' => $this->trans('Status', [], 'Admin.Global'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'class' => 'fixed-width-xs',
                'orderby' => false,
            ],
            'date_upd' => [
                'title' => $this->trans('Last Modified', [], 'Admin.Global'),
                'type' => 'datetime',
                'class' => 'fixed-width-lg',
            ],
        ];
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->trans('Allowed Delivery Village', [], 'Modules.Shippingvillages.Admin'),
                'icon' => 'icon-map-marker',
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->trans('Village Name', [], 'Modules.Shippingvillages.Admin'),
                    'name' => 'name',
                    'required' => true,
                    'hint' => $this->trans('Name of the village or municipal district (e.g. Šamorín, Kvetoslavov, Báč).', [], 'Modules.Shippingvillages.Admin'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Postal Code', [], 'Admin.Global'),
                    'name' => 'zipcode',
                    'required' => false,
                    'hint' => $this->trans('Optional postal code (e.g. 931 01).', [], 'Modules.Shippingvillages.Admin'),
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Shipping Price', [], 'Modules.Shippingvillages.Admin'),
                    'name' => 'price',
                    'required' => false,
                    'suffix' => isset($this->context->currency->sign) ? $this->context->currency->sign : '€',
                    'hint' => $this->trans('Delivery shipping price for this village (e.g. 5.00).', [], 'Modules.Shippingvillages.Admin'),
                    'class' => 'fixed-width-sm',
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Active for Delivery', [], 'Modules.Shippingvillages.Admin'),
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->trans('Enabled', [], 'Admin.Global'),
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->trans('Disabled', [], 'Admin.Global'),
                        ],
                    ],
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Position', [], 'Admin.Global'),
                    'name' => 'position',
                    'required' => false,
                    'class' => 'fixed-width-sm',
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Admin.Actions'),
            ],
        ];

        return parent::renderForm();
    }
}
