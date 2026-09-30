<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/classes/ViragosCheckoutAddressSteps.php';

class ViragosCheckout extends Module
{
    public function __construct()
    {
        $this->name = 'viragoscheckout';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Viragos';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = [
            'min' => '9.1.0',
            'max' => '9.1.99',
        ];

        parent::__construct();

        $this->displayName = $this->l('Separate Checkout Address Steps');
        $this->description = $this->l('Separates shipping and billing addresses into two checkout steps.');
    }

    public function install(): bool
    {
        return parent::install() && $this->registerHook('actionCheckoutRender');
    }

    public function hookActionCheckoutRender(array $params): void
    {
        if (empty($params['checkoutProcess']) || !$params['checkoutProcess'] instanceof CheckoutProcess) {
            return;
        }

        /** @var CheckoutProcess $process */
        $process = $params['checkoutProcess'];
        $translator = $this->context->getTranslator();
        $isVirtualCart = $this->context->cart->isVirtualCart();
        $guestCheckoutAllowed = (bool) Configuration::get('PS_GUEST_CHECKOUT_ENABLED');
        $skipGuestPersonalInformation = !$isVirtualCart
            && $guestCheckoutAllowed
            && (!$this->context->customer->isLogged() || $this->context->customer->is_guest);

        $this->context->smarty->assign(
            'viragos_skip_guest_personal_information',
            $skipGuestPersonalInformation
        );

        $steps = [];
        $replacedAddressStep = false;

        foreach ($process->getSteps() as $step) {
            if ($skipGuestPersonalInformation && $step instanceof CheckoutPersonalInformationStep) {
                continue;
            }

            if (!$step instanceof CheckoutAddressesStep) {
                $steps[] = $step;
                continue;
            }

            $replacedAddressStep = true;
            if (!$isVirtualCart) {
                $steps[] = new ViragosCheckoutShippingAddressStep(
                    $this->context,
                    $translator,
                    $this->makeAddressForm($translator),
                    $skipGuestPersonalInformation ? $this->makeCustomerForm($translator) : null,
                    $skipGuestPersonalInformation ? $this->makeCustomerLoginForm($translator) : null,
                    $skipGuestPersonalInformation
                );
            }
            $steps[] = new ViragosCheckoutBillingAddressStep(
                $this->context,
                $translator,
                $this->makeAddressForm($translator),
                !$isVirtualCart
            );
        }

        if (!$replacedAddressStep) {
            return;
        }

        $process->setSteps($steps);
        foreach ($steps as $step) {
            if ($step instanceof ViragosCheckoutAddressStep) {
                $step->setCheckoutProcess($process);
            }
        }
    }

    private function makeAddressForm($translator): CustomerAddressForm
    {
        $availableCountries = Configuration::get('PS_RESTRICT_DELIVERED_COUNTRIES')
            ? Carrier::getDeliveredCountries($this->context->language->id, true, true)
            : Country::getCountries($this->context->language->id, true);

        $form = new CustomerAddressForm(
            $this->context->smarty,
            $this->context->language,
            $translator,
            new CustomerAddressPersister(
                $this->context->customer,
                $this->context->cart,
                Tools::getToken(true, $this->context)
            ),
            new CustomerAddressFormatter(
                $this->context->country,
                $translator,
                $availableCountries
            )
        );

        return $form->setAction($this->context->link->getPageLink('order'));
    }

    private function makeCustomerForm($translator): CustomerForm
    {
        $guestAllowed = (bool) Configuration::get('PS_GUEST_CHECKOUT_ENABLED');
        $controller = $this->context->controller;
        $customer = new Customer();
        $formatter = new CustomerFormatter($translator, $this->context->language);
        $formatter
            ->setAskForPartnerOptin(Configuration::get('PS_CUSTOMER_OPTIN'))
            ->setAskForBirthdate(Configuration::get('PS_CUSTOMER_BIRTHDATE'))
            ->setPartnerOptinRequired($customer->isFieldRequired('optin'));

        $form = new CustomerForm(
            $this->context->smarty,
            $this->context,
            $translator,
            $formatter,
            new CustomerPersister(
                $this->context,
                $controller->get('hashing'),
                $translator,
                $guestAllowed
            ),
            $controller->getTemplateVarUrls()
        );

        return $form
            ->setGuestAllowed($guestAllowed)
            ->setAction($this->context->link->getPageLink('order'));
    }

    private function makeCustomerLoginForm($translator): CustomerLoginForm
    {
        $controller = $this->context->controller;
        $form = new CustomerLoginForm(
            $this->context->smarty,
            $this->context,
            $translator,
            new CustomerLoginFormatter($translator),
            $controller->getTemplateVarUrls()
        );

        return $form->setAction($this->context->link->getPageLink('order'));
    }
}