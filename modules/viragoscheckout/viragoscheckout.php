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
        $steps = [];
        $replacedAddressStep = false;

        foreach ($process->getSteps() as $step) {
            if (!$step instanceof CheckoutAddressesStep) {
                $steps[] = $step;
                continue;
            }

            $replacedAddressStep = true;
            if (!$this->context->cart->isVirtualCart()) {
                $steps[] = new ViragosCheckoutShippingAddressStep(
                    $this->context,
                    $translator,
                    $this->makeAddressForm($translator)
                );
            }
            $steps[] = new ViragosCheckoutBillingAddressStep(
                $this->context,
                $translator,
                $this->makeAddressForm($translator),
                !$this->context->cart->isVirtualCart()
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
}