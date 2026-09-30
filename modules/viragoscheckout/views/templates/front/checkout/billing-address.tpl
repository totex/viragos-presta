{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}
  <div class="js-address-form">
    {if $show_address_form}
      {render file='checkout/_partials/address-form.tpl'
        ui=$address_form
        type='invoice'
        use_same_address=$same_as_shipping
        split_address_steps=true
        has_shipping_address=$has_shipping_address
        form_has_continue_button=true
      }
    {else}
      <form method="POST" action="{url entity='order'}">
        {if $has_shipping_address}
          <div class="mb-3 form-check">
            <input type="hidden" name="same_as_shipping" value="0">
            <input class="form-check-input" name="same_as_shipping" id="same_as_shipping" type="checkbox" value="1" {if $same_as_shipping}checked{/if} onchange="this.form.submit()">
            <label class="form-check-label" for="same_as_shipping">{l s='Same as Shipping' mod='viragoscheckout'}</label>
          </div>
        {/if}

        {if !$same_as_shipping && $customer.addresses|count > 0}
          <div class="address__list js-address-selector">
            {include file='checkout/_partials/address-selector-block.tpl'
              addresses=$customer.addresses
              name='id_address_invoice'
              selected=$id_address_invoice
              type='invoice'
              interactive=false
            }
          </div>
        {/if}
        {if $invoice_address_invalid && $address_warning}
          <p class="alert alert-danger">{$address_warning.exception}</p>
        {/if}

        <a href="{url entity='order' params=['newAddress' => 'invoice']}" class="btn btn-outline-primary">
          {l s='Add billing address' mod='viragoscheckout'}
        </a>
        <div class="buttons-wrapper buttons-wrapper--split mt-3">
          <button class="btn btn-outline-primary js-back" type="button" data-step="{if $has_shipping_address}viragos-checkout-shipping-address-step{else}checkout-personal-information-step{/if}">
            {if $has_shipping_address}
              {l s='Back to Shipping Address' mod='viragoscheckout'}
            {else}
              {l s='Back to Personal Information' d='Shop.Theme.Actions'}
            {/if}
          </button>
          <button type="submit" class="btn btn-primary" name="confirm-addresses" value="1">
            {l s='Continue' d='Shop.Theme.Actions'}
          </button>
        </div>
      </form>
    {/if}
  </div>
{/block}