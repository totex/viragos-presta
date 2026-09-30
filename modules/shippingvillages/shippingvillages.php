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

class ShippingVillages extends Module
{
    public function __construct()
    {
        $this->name = 'shippingvillages';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Viragos';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.0.0',
            'max' => _PS_VERSION_,
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Shipping Villages - Šamorín Delivery Zone');
        $this->description = $this->l('Restricts shipping delivery addresses to allowed villages around Šamorín while keeping invoice addresses flexible.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module? All village data will be deleted.');
    }

    public function install(): bool
    {
        if (
            !parent::install()
            || !$this->createDatabaseTables()
            || !$this->seedInitialVillages()
            || !$this->installTab()
            || !$this->registerHook('actionValidateCustomerAddressForm')
            || !$this->registerHook('actionFrontControllerSetMedia')
            || !$this->registerHook('displayHeader')
            || !$this->registerHook('actionCheckoutStepRenderTemplate')
            || !$this->registerHook('actionCarrierProcess')
            || !$this->registerHook('actionCartGetPackageShippingCost')
        ) {
            return false;
        }

        return true;
    }

    public function uninstall(): bool
    {
        return $this->uninstallTab()
            && $this->dropDatabaseTables()
            && parent::uninstall();
    }

    protected function createDatabaseTables(): bool
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'shipping_village` (
            `id_shipping_village` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(128) NOT NULL,
            `zipcode` VARCHAR(32) NULL,
            `price` DECIMAL(20, 6) NOT NULL DEFAULT \'0.000000\',
            `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            `position` INT(11) UNSIGNED NOT NULL DEFAULT 0,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_shipping_village`),
            KEY `active_position` (`active`, `position`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;';

        return (bool) Db::getInstance()->execute($sql);
    }

    protected function dropDatabaseTables(): bool
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'shipping_village`;';

        return (bool) Db::getInstance()->execute($sql);
    }

    protected function seedInitialVillages(): bool
    {
        $initialVillages = [
            ['name' => 'Šamorín', 'zipcode' => '931 01'],
            ['name' => 'Mliečno', 'zipcode' => '931 01'],
            ['name' => 'Čilistov', 'zipcode' => '931 01'],
            ['name' => 'Kvetoslavov', 'zipcode' => '930 41'],
            ['name' => 'Báč', 'zipcode' => '930 30'],
            ['name' => 'Dunajská Lužná', 'zipcode' => '900 42'],
            ['name' => 'Hamuliakovo', 'zipcode' => '900 43'],
            ['name' => 'Kalinkovo', 'zipcode' => '900 43'],
            ['name' => 'Rohovce', 'zipcode' => '930 30'],
            ['name' => 'Veľká Paka', 'zipcode' => '930 51'],
            ['name' => 'Malá Paka', 'zipcode' => '930 51'],
            ['name' => 'Lehnice', 'zipcode' => '930 37'],
            ['name' => 'Macov', 'zipcode' => '930 32'],
            ['name' => 'Trnávka', 'zipcode' => '930 32'],
            ['name' => 'Blatná na Ostrove', 'zipcode' => '930 32'],
            ['name' => 'Holice', 'zipcode' => '930 34'],
        ];

        $now = date('Y-m-d H:i:s');
        $position = 0;

        foreach ($initialVillages as $villageData) {
            $village = new ShippingVillage();
            $village->name = $villageData['name'];
            $village->zipcode = $villageData['zipcode'];
            $village->active = true;
            $village->position = $position++;
            $village->date_add = $now;
            $village->date_upd = $now;

            if (!$village->save()) {
                return false;
            }
        }

        return true;
    }

    public function installTab(): bool
    {
        $tabId = (int) Tab::getIdFromClassName('AdminShippingVillages');
        if ($tabId) {
            return true;
        }

        $tab = new Tab();
        $tab->active = true;
        $tab->class_name = 'AdminShippingVillages';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Allowed Delivery Villages';
        }

        $parentTabId = (int) Tab::getIdFromClassName('AdminParentShipping');
        if (!$parentTabId) {
            $parentTabId = (int) Tab::getIdFromClassName('AdminCarriers');
        }
        $tab->id_parent = $parentTabId > 0 ? $parentTabId : 0;
        $tab->module = $this->name;

        return (bool) $tab->add();
    }

    public function uninstallTab(): bool
    {
        $tabId = (int) Tab::getIdFromClassName('AdminShippingVillages');
        if ($tabId) {
            $tab = new Tab($tabId);
            return (bool) $tab->delete();
        }

        return true;
    }

    public function getContent()
    {
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminShippingVillages'));
    }

    public function hookActionFrontControllerSetMedia()
    {
        $activeVillages = ShippingVillage::getActiveVillages();
        $this->context->smarty->assign([
            'shipping_villages' => $activeVillages,
            'shipping_currency_iso' => isset($this->context->currency->iso_code) ? $this->context->currency->iso_code : 'EUR',
        ]);

        if (is_callable([$this->context->smarty, 'registerClass'])) {
            @$this->context->smarty->registerClass('ShippingVillage', 'ShippingVillage');
        }

        $script = $this->context->shop->theme->getName() === 'flowershop'
            ? 'checkout-village-postcode.js'
            : 'checkout-villages.js';

        $this->context->controller->registerJavascript(
            'module-shippingvillages-checkout',
            'modules/' . $this->name . '/views/js/' . $script,
            [
                'position' => 'bottom',
                'priority' => 150,
            ]
        );

        Media::addJsDef([
            'shippingVillagesList' => $activeVillages,
            'shippingVillagesCurrency' => [
                'iso_code' => isset($this->context->currency->iso_code) ? $this->context->currency->iso_code : 'EUR',
                'sign' => isset($this->context->currency->sign) ? $this->context->currency->sign : '€',
                'format' => isset($this->context->currency->format) ? $this->context->currency->format : '€%s',
                'precision' => isset($this->context->currency->precision) ? (int) $this->context->currency->precision : 2,
            ],
            'shippingVillagesFreeText' => $this->l('Free'),
        ]);

        $this->enforceDeliveryAddressValidation();
    }

    public function hookDisplayHeader()
    {
        $activeVillages = ShippingVillage::getActiveVillages();
        $this->context->smarty->assign([
            'shipping_villages' => $activeVillages,
            'shipping_currency_iso' => isset($this->context->currency->iso_code) ? $this->context->currency->iso_code : 'EUR',
        ]);

        if (is_callable([$this->context->smarty, 'registerClass'])) {
            @$this->context->smarty->registerClass('ShippingVillage', 'ShippingVillage');
        }

        Media::addJsDef([
            'shippingVillagesList' => $activeVillages,
            'shippingVillagesCurrency' => [
                'iso_code' => isset($this->context->currency->iso_code) ? $this->context->currency->iso_code : 'EUR',
                'sign' => isset($this->context->currency->sign) ? $this->context->currency->sign : '€',
                'format' => isset($this->context->currency->format) ? $this->context->currency->format : '€%s',
                'precision' => isset($this->context->currency->precision) ? (int) $this->context->currency->precision : 2,
            ],
            'shippingVillagesFreeText' => $this->l('Free'),
        ]);

        $this->enforceDeliveryAddressValidation();
    }

    public function hookActionCheckoutStepRenderTemplate(&$params)
    {
        $this->enforceDeliveryAddressValidation();
    }

    public function hookActionCarrierProcess(&$params)
    {
        $this->enforceDeliveryAddressValidation();
    }

    /**
     * Enforce validation on selected delivery addresses in checkout
     */
    protected function enforceDeliveryAddressValidation()
    {
        if (!($this->context->controller instanceof OrderController) || empty($this->context->cart)) {
            return;
        }

        $idDelivery = (int) ($this->context->cart->id_address_delivery ?? 0);
        if ($idDelivery > 0) {
            $deliveryAddress = new Address($idDelivery);
            if (Validate::isLoadedObject($deliveryAddress) && !empty($deliveryAddress->city)) {
                if (!ShippingVillage::isAllowedVillage($deliveryAddress->city)) {
                    if (!isset($this->context->controller->checkoutWarning['invalid_addresses'])) {
                        $this->context->controller->checkoutWarning['invalid_addresses'] = [];
                    }
                    if (!in_array($idDelivery, $this->context->controller->checkoutWarning['invalid_addresses'])) {
                        $this->context->controller->checkoutWarning['invalid_addresses'][] = $idDelivery;
                    }
                    $this->context->controller->checkoutWarning['address'] = [
                        'id_address' => $idDelivery,
                        'exception' => $this->l('Delivery is only available to selected villages around Šamorín. The selected address has an unsupported delivery city (' . $deliveryAddress->city . '). Please select or enter an address within our delivery zone.'),
                    ];
                }
            }
        }
    }

    /**
     * Validate delivery address form submissions
     *
     * @param array $params
     * @return bool
     */
    public function hookActionValidateCustomerAddressForm(&$params)
    {
        if (!isset($params['form'])) {
            return true;
        }

        /** @var CustomerAddressForm $form */
        $form = $params['form'];
        
        $type = Tools::getValue('type', '');
        if (!$type && isset($_POST['type'])) {
            $type = (string) $_POST['type'];
        }
        if (!$type && Tools::getValue('saveAddress') !== false) {
            $type = (string) Tools::getValue('saveAddress');
        }

        // If form is specifically for invoice, skip village restriction (FR-3: Any city allowed for invoice)
        if ($type === 'invoice') {
            return true;
        }

        // Validate delivery address form (FR-4)
        $cityField = $form->getField('city');
        if ($cityField && !empty($cityField->getValue())) {
            $cityName = (string) $cityField->getValue();
            
            $isCheckout = ($this->context->controller instanceof OrderController);
            if ($type === 'delivery' || ($isCheckout && !Tools::getIsset('invoice_address'))) {
                if (!ShippingVillage::isAllowedVillage($cityName)) {
                    $cityField->addError(
                        $this->l('Delivery is only available to selected villages around Šamorín (e.g. Šamorín, Kvetoslavov, Báč, Mliečno, etc.). Please select an allowed village from the list.')
                    );
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Calculate / adjust shipping cost based on delivery village
     *
     * @param array $params
     */
    public function hookActionCartGetPackageShippingCost(&$params)
    {
        /** @var Cart|null $cart */
        $cart = $params['cart'] ?? null;
        if (!$cart || !($cart instanceof Cart)) {
            return;
        }

        $idAddressDelivery = (int) ($cart->id_address_delivery ?? 0);
        if ($idAddressDelivery <= 0) {
            return;
        }

        $deliveryAddress = new Address($idAddressDelivery);
        if (!Validate::isLoadedObject($deliveryAddress) || empty($deliveryAddress->city)) {
            return;
        }

        $village = ShippingVillage::getVillageByName($deliveryAddress->city);
        if ($village && isset($village['price'])) {
            $villagePrice = (float) $village['price'];
            if ($villagePrice > 0 && isset($params['shippingCost']) && $params['shippingCost'] !== false) {
                $params['shippingCost'] = (float) $params['shippingCost'] + $villagePrice;
            }
        }
    }
}
