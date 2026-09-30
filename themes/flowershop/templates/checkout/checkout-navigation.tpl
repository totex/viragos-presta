{**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *}
{$componentName = 'checkout-steps'}
{$hasNotifications = false}
{if isset($notifications)}
  {$hasNotifications = $notifications.warning|@count > 0 || $notifications.error|@count > 0 || $notifications.success|@count > 0 || $notifications.info|@count > 0}
{/if}

{block name='checkout_steps'}
  {$stepNumber = 1}
  <div class="{$componentName} {if $hasNotifications} {$componentName}--has-notifications{/if}">
    <div class="{$componentName}__desktop">
      <ul class="{$componentName}__list" role="tablist">
        {if !$viragos_skip_guest_personal_information}
          {include file='checkout/_partials/checkout-navigation-step.tpl'
            number=$stepNumber
            step="checkout-personal-information-step"
            title="{l s='Personal Information' d='Shop.Theme.Checkout'}"
          }
          {$stepNumber = $stepNumber + 1}
        {/if}

        {if !$cart.is_virtual}
          {include file='checkout/_partials/checkout-navigation-step.tpl'
            number=$stepNumber
            step="viragos-checkout-shipping-address-step"
            title="{l s='Shipping Address' d='Shop.Theme.Checkout'}"
          }
          {$stepNumber = $stepNumber + 1}
        {/if}

        {include file='checkout/_partials/checkout-navigation-step.tpl'
          number=$stepNumber
          step="viragos-checkout-billing-address-step"
          title="{l s='Billing Address' d='Shop.Theme.Checkout'}"
        }
        {$stepNumber = $stepNumber + 1}

        {if !$cart.is_virtual}
          {include file='checkout/_partials/checkout-navigation-step.tpl'
            number=$stepNumber
            step="checkout-delivery-step"
            title="{l s='Shipping method' d='Shop.Theme.Checkout'}"
          }
          {$stepNumber = $stepNumber + 1}
        {/if}

        {include file='checkout/_partials/checkout-navigation-step.tpl'
          number=$stepNumber
          step="checkout-payment-step"
          title="{l s='Payment' d='Shop.Theme.Checkout'}"
        }
      </ul>
    </div>

    <div class="{$componentName}__mobile">
      <div class="{$componentName}__left">
        {include file='components/progress-circle.tpl' classes="text-success" size=74 stroke=4}
      </div>

      <div class="{$componentName}__right">
        {if !$viragos_skip_guest_personal_information}
          {include file='checkout/_partials/checkout-navigation-step-mobile.tpl'
            step="checkout-personal-information-step"
            title="{l s='Personal Information' d='Shop.Theme.Checkout'}"
            subtitle="{if $cart.is_virtual}{l s='Next: Billing Address' d='Shop.Theme.Checkout'}{else}{l s='Next: Shipping Address' d='Shop.Theme.Checkout'}{/if}"
          }
        {/if}

        {if !$cart.is_virtual}
          {include file='checkout/_partials/checkout-navigation-step-mobile.tpl'
            step="viragos-checkout-shipping-address-step"
            title="{l s='Shipping Address' d='Shop.Theme.Checkout'}"
            subtitle="{l s='Next: Billing Address' d='Shop.Theme.Checkout'}"
          }
        {/if}

        {include file='checkout/_partials/checkout-navigation-step-mobile.tpl'
          step="viragos-checkout-billing-address-step"
          title="{l s='Billing Address' d='Shop.Theme.Checkout'}"
          subtitle="{if $cart.is_virtual}{l s='Next: Payment' d='Shop.Theme.Checkout'}{else}{l s='Next: Shipping Method' d='Shop.Theme.Checkout'}{/if}"
        }

        {if !$cart.is_virtual}
          {include file='checkout/_partials/checkout-navigation-step-mobile.tpl'
            step="checkout-delivery-step"
            title="{l s='Shipping Method' d='Shop.Theme.Checkout'}"
            subtitle="{l s='Next: Payment' d='Shop.Theme.Checkout'}"
          }
        {/if}

        {include file='checkout/_partials/checkout-navigation-step-mobile.tpl'
          step="checkout-payment-step"
          title="{l s='Payment' d='Shop.Theme.Checkout'}"
        }
      </div>
    </div>
  </div>
{/block}