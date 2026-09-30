<?php

use Symfony\Contracts\Translation\TranslatorInterface;

abstract class ViragosCheckoutAddressStep extends AbstractCheckoutStep
{
    protected $addressForm;
    protected $addressType;
    protected $showAddressForm = false;

    public function __construct(
        Context $context,
        TranslatorInterface $translator,
        CustomerAddressForm $addressForm,
        string $addressType
    ) {
        parent::__construct($context, $translator);
        $this->addressForm = $addressForm;
        $this->addressType = $addressType;
    }

    protected function prepareAddressForm(array $requestParameters): void
    {
        $this->addressForm->setAction($this->getCheckoutSession()->getCheckoutURL());
        $this->addressForm->fillWith([
            'firstname' => $this->getCheckoutSession()->getCustomer()->firstname,
            'lastname' => $this->getCheckoutSession()->getCustomer()->lastname,
        ]);

        if (($requestParameters['newAddress'] ?? null) === $this->addressType) {
            $this->showAddressForm = true;
            $this->setCurrent(true);
            $this->addressForm->fillWith($requestParameters);
        } elseif (($requestParameters['editAddress'] ?? null) === $this->addressType) {
            $this->showAddressForm = true;
            $this->setCurrent(true);
            $this->addressForm->loadAddressById((int) ($requestParameters['id_address'] ?? 0));
        } elseif (($requestParameters['cancelAddress'] ?? null) === $this->addressType) {
            $this->setCurrent(true);
        }

        if (($requestParameters['saveAddress'] ?? null) === $this->addressType) {
            if (!$this->addressForm->fillWith($requestParameters)->submit()) {
                $this->showAddressForm = true;
                $this->setCurrent(true);
                $this->getCheckoutProcess()->setHasErrors(true);
                return;
            }

            $addressId = (int) $this->addressForm->getAddress()->id;
            $this->setSelectedAddress($addressId);
        }
    }

    protected function isValidCustomerAddress(int $addressId): bool
    {
        return $addressId > 0 && Customer::customerHasAddress(
            (int) $this->getCheckoutSession()->getCustomer()->id,
            $addressId
        );
    }

    abstract protected function setSelectedAddress(int $addressId): void;

    protected function renderAddressStep(array $parameters)
    {
        $deliveryAddressId = (int) $this->getCheckoutSession()->getIdAddressDelivery();
        $invoiceAddressId = (int) $this->getCheckoutSession()->getIdAddressInvoice();
        $controllerProperties = is_object($this->context->controller)
            ? get_object_vars($this->context->controller)
            : [];
        $warnings = $controllerProperties['checkoutWarning'] ?? [];
        $invalidAddressIds = $warnings['invalid_addresses'] ?? [];

        return $this->renderTemplate($this->getTemplate(), [], array_merge([
            'address_form' => $this->addressForm->getProxy(),
            'show_address_form' => $this->showAddressForm,
            'address_type' => $this->addressType,
            'id_address_delivery' => $deliveryAddressId,
            'id_address_invoice' => $invoiceAddressId,
            'address_warning' => $warnings['address'] ?? false,
            'delivery_address_invalid' => in_array($deliveryAddressId, $invalidAddressIds),
            'invoice_address_invalid' => in_array($invoiceAddressId, $invalidAddressIds),
            'token' => Tools::getToken(true, $this->context),
        ], $parameters));
    }
}

class ViragosCheckoutShippingAddressStep extends ViragosCheckoutAddressStep
{
    protected $template = 'module:viragoscheckout/views/templates/front/checkout/shipping-address.tpl';
    private $customerForm;
    private $loginForm;
    private $collectGuestIdentity;
    private $showLoginForm = false;

    public function __construct(
        Context $context,
        TranslatorInterface $translator,
        CustomerAddressForm $addressForm,
        ?CustomerForm $customerForm = null,
        ?CustomerLoginForm $loginForm = null,
        bool $collectGuestIdentity = false
    ) {
        parent::__construct($context, $translator, $addressForm, 'delivery');
        $this->customerForm = $customerForm;
        $this->loginForm = $loginForm;
        $this->collectGuestIdentity = $collectGuestIdentity;
    }

    public function handleRequest(array $requestParameters = [])
    {
        $this->setTitle($this->getTranslator()->trans('Shipping Address', [], 'Shop.Theme.Checkout'));

        if ($this->handleGuestIdentity($requestParameters)) {
            return $this;
        }

        $this->prepareAddressForm($requestParameters);
        if ($this->getCheckoutProcess()->hasErrors()) {
            return $this;
        }

        if (isset($requestParameters['confirm-addresses'])) {
            $addressId = (int) ($requestParameters['id_address_delivery'] ?? $this->getCheckoutSession()->getIdAddressDelivery());
            if (!$this->isValidCustomerAddress($addressId)) {
                $this->getCheckoutProcess()->setHasErrors(true);
                $this->setCurrent(true);
                return $this;
            }

            $this->setSelectedAddress($addressId);
            $this->getCheckoutSession()->setIdAddressInvoice(null);
            $this->setComplete(true);
            $this->setNextStepAsCurrent();
        }

        if (!$this->getCheckoutSession()->getIdAddressDelivery()) {
            $this->showAddressForm = true;
            $this->setCurrent(true);
        }

        return $this;
    }

    private function handleGuestIdentity(array $requestParameters): bool
    {
        $customer = $this->getCheckoutSession()->getCustomer();
        if (!$this->collectGuestIdentity || $customer->id || !$this->customerForm || !$this->loginForm) {
            return false;
        }

        $this->customerForm
            ->setAction($this->getCheckoutSession()->getCheckoutURL())
            ->fillFromCustomer($customer);
        $this->loginForm->setAction($this->getCheckoutSession()->getCheckoutURL());

        if (isset($requestParameters['submitCreate'])) {
            $this->customerForm->fillWith($requestParameters);
            $hookResult = array_reduce(
                Hook::exec('actionSubmitAccountBefore', [], null, true),
                function ($carry, $item) {
                    return $carry && $item;
                },
                true
            );

            if (!$hookResult || !$this->customerForm->submit()) {
                $this->setCurrent(true);
                $this->getCheckoutProcess()->setHasErrors(true);
            } else {
                $this->setCurrent(true);
            }

            return true;
        }

        if (isset($requestParameters['submitLogin'])) {
            $this->loginForm->fillWith($requestParameters);
            if (!$this->loginForm->submit()) {
                $this->showLoginForm = true;
                $this->getCheckoutProcess()->setHasErrors(true);
            }
            $this->setCurrent(true);

            return true;
        }

        if (array_key_exists('login', $requestParameters)) {
            $this->showLoginForm = true;
        }

        $this->setCurrent(true);

        return true;
    }

    protected function setSelectedAddress(int $addressId): void
    {
        $this->getCheckoutSession()->setIdAddressDelivery($addressId);
    }

    public function render(array $extraParams = [])
    {
        $customer = $this->getCheckoutSession()->getCustomer();

        return $this->renderAddressStep(array_merge($extraParams, [
            'identity_required' => $this->collectGuestIdentity && !$customer->id,
            'customer_form' => $this->customerForm ? $this->customerForm->getProxy() : null,
            'login_form' => $this->loginForm ? $this->loginForm->getProxy() : null,
            'show_login_form' => $this->showLoginForm,
            'guest_allowed' => (bool) Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
            'has_personal_information_step' => !$this->collectGuestIdentity,
        ]));
    }
}

class ViragosCheckoutBillingAddressStep extends ViragosCheckoutAddressStep
{
    protected $template = 'module:viragoscheckout/views/templates/front/checkout/billing-address.tpl';

    private $sameAsShipping;
    private $hasShippingAddress;

    public function __construct(
        Context $context,
        TranslatorInterface $translator,
        CustomerAddressForm $addressForm,
        bool $hasShippingAddress
    ) {
        parent::__construct($context, $translator, $addressForm, 'invoice');
        $this->hasShippingAddress = $hasShippingAddress;
        $this->sameAsShipping = $hasShippingAddress;
    }

    public function getDataToPersist()
    {
        return ['same_as_shipping' => $this->sameAsShipping];
    }

    public function restorePersistedData(array $data)
    {
        if (array_key_exists('same_as_shipping', $data)) {
            $this->sameAsShipping = (bool) $data['same_as_shipping'];
        }

        return $this;
    }

    public function handleRequest(array $requestParameters = [])
    {
        if (($requestParameters['newAddress'] ?? null) === 'invoice'
            || ($requestParameters['editAddress'] ?? null) === 'invoice'
        ) {
            $this->sameAsShipping = false;
        }

        $this->prepareAddressForm($requestParameters);
        if ($this->getCheckoutProcess()->hasErrors()) {
            return $this;
        }

        $hasBillingAction = array_key_exists('same_as_shipping', $requestParameters)
            || isset($requestParameters['id_address_invoice'])
            || ($requestParameters['saveAddress'] ?? null) === 'invoice';
        if ($hasBillingAction && $this->hasShippingAddress) {
            $this->sameAsShipping = !empty($requestParameters['same_as_shipping']);
        }
        if (array_key_exists('same_as_shipping', $requestParameters) && !$this->sameAsShipping) {
            $this->setComplete(false);
            $this->setCurrent(true);
        }

        if ($this->sameAsShipping && $this->hasShippingAddress && $hasBillingAction) {
            $this->getCheckoutSession()->setIdAddressInvoice(
                (int) $this->getCheckoutSession()->getIdAddressDelivery()
            );
            $this->setComplete(true);
            if (isset($requestParameters['confirm-addresses']) || array_key_exists('same_as_shipping', $requestParameters)) {
                $this->setNextStepAsCurrent();
            }
        } elseif (isset($requestParameters['confirm-addresses']) && $hasBillingAction) {
            $addressId = (int) ($requestParameters['id_address_invoice'] ?? $this->getCheckoutSession()->getIdAddressInvoice());
            if (!$this->isValidCustomerAddress($addressId)) {
                $this->getCheckoutProcess()->setHasErrors(true);
                $this->setCurrent(true);
                return $this;
            }

            $this->setSelectedAddress($addressId);
            $this->setComplete(true);
            $this->setNextStepAsCurrent();
        } elseif (!$this->getCheckoutSession()->getIdAddressInvoice()) {
            $this->setComplete(false);
            if ($this->isReachable()) {
                $this->setCurrent(true);
            }
            if ($this->getCheckoutSession()->getCustomerAddressesCount() === 0) {
                $this->showAddressForm = true;
            }
        }

        $this->setTitle($this->getTranslator()->trans('Billing Address', [], 'Shop.Theme.Checkout'));

        return $this;
    }

    protected function setSelectedAddress(int $addressId): void
    {
        $this->getCheckoutSession()->setIdAddressInvoice($addressId);
    }

    public function render(array $extraParams = [])
    {
        return $this->renderAddressStep(array_merge($extraParams, [
            'same_as_shipping' => $this->sameAsShipping,
            'has_shipping_address' => $this->hasShippingAddress,
        ]));
    }
}