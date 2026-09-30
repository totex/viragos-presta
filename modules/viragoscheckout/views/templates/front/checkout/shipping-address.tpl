{extends file='checkout/_partials/steps/checkout-step.tpl'}

{block name='step_content'}
  <div class="js-address-form">
    {if $show_address_form}
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
        <div class="buttons-wrapper buttons-wrapper--split mt-3">
          <button class="btn btn-outline-primary js-back" type="button" data-step="checkout-personal-information-step">
            {l s='Back to Personal Information' d='Shop.Theme.Actions'}
          </button>
          <button type="submit" class="btn btn-primary" name="confirm-addresses" value="1">
            {l s='Continue' d='Shop.Theme.Actions'}
          </button>
        </div>
      </form>
    {/if}
  </div>
{/block}