{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}
  <div class="js-address-form">
    {if $identity_required}
      <ul class="nav nav-underline" id="shipping-identity-tabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button
            class="nav-link {if !$show_login_form}active{/if}"
            data-bs-toggle="tab"
            data-bs-target="#shipping-guest-form"
            type="button"
            role="tab"
            aria-controls="shipping-guest-form"
            aria-selected="{if !$show_login_form}true{else}false{/if}"
          >
            {l s='Order as a guest' d='Shop.Theme.Checkout'}
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button
            class="nav-link {if $show_login_form}active{/if}"
            data-bs-toggle="tab"
            data-bs-target="#shipping-login-form"
            type="button"
            role="tab"
            aria-controls="shipping-login-form"
            aria-selected="{if $show_login_form}true{else}false{/if}"
          >
            {l s='Sign in' d='Shop.Theme.Actions'}
          </button>
        </li>
      </ul>

      <div class="tab-content" id="shipping-identity-tabs-content">
        <div class="tab-pane fade{if !$show_login_form} show active{/if}" id="shipping-guest-form" role="tabpanel">
          {render file='checkout/_partials/customer-form.tpl' ui=$customer_form guest_allowed=$guest_allowed}
          <div class="buttons-wrapper buttons-wrapper--end mt-3">
            <button
              class="btn btn-primary"
              type="submit"
              form="customer-form"
              name="continue"
              value="1"
              data-link-action="register-new-customer"
              data-ps-action="form-validation-submit"
            >
              {l s='Continue to Shipping Address' mod='viragoscheckout'}
              <i class="material-icons rtl-flip" aria-hidden="true">&#xE5C8;</i>
            </button>
          </div>
        </div>
        <div class="tab-pane fade{if $show_login_form} show active{/if}" id="shipping-login-form" role="tabpanel">
          {render file='checkout/_partials/login-form.tpl' ui=$login_form}
        </div>
      </div>
    {elseif $show_address_form}
      {render file='checkout/_partials/address-form.tpl'
        ui=$address_form
        type='delivery'
        use_same_address=false
        split_address_steps=true
        has_shipping_address=true
        form_has_continue_button=true
      }
    {else}
      <form method="POST" action="{url entity='order'}">
        {if $customer.addresses|count > 0}
          <div class="address__list js-address-selector">
            {include file='checkout/_partials/address-selector-block.tpl'
              addresses=$customer.addresses
              name='id_address_delivery'
              selected=$id_address_delivery
              type='delivery'
              interactive=false
            }
          </div>
        {/if}
        {if $delivery_address_invalid && $address_warning}
          <p class="alert alert-danger">{$address_warning.exception}</p>
        {/if}
        <a href="{url entity='order' params=['newAddress' => 'delivery']}" class="btn btn-outline-primary">
          {l s='Add new address' d='Shop.Theme.Actions'}
        </a>
        <div class="buttons-wrapper {if $has_personal_information_step}buttons-wrapper--split{else}buttons-wrapper--end{/if} mt-3">
          {if $has_personal_information_step}
            <button class="btn btn-outline-primary js-back" type="button" data-step="checkout-personal-information-step">
              {l s='Back to Personal Information' d='Shop.Theme.Actions'}
            </button>
          {/if}
          <button type="submit" class="btn btn-primary" name="confirm-addresses" value="1">
            {l s='Continue' d='Shop.Theme.Actions'}
          </button>
        </div>
      </form>
    {/if}
  </div>
{/block}