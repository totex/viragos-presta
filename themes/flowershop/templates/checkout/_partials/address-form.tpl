{extends file='customer/_partials/address-form.tpl'}

{block name='form_field'}
  {if $field.name eq "alias" and $customer.is_guest}
    {* we don't ask for alias here if customer is not registered *}
  {elseif $field.name eq "city" and (!isset($type) || $type neq "invoice")}
    {if !isset($shipping_villages)}
      {if class_exists('ShippingVillage')}
        {$shipping_villages = ShippingVillage::getActiveVillages()}
      {/if}
    {/if}
    {if isset($shipping_villages) && $shipping_villages|count > 0}
      <div class="mb-3{if !empty($field.errors)} has-error{/if}">
        <label class="form-label{if $field.required} required{/if}" for="field-{$field.name}">
          {$field.label}
        </label>
        <select id="field-{$field.name}" class="form-select js-shipping-village-select" name="{$field.name}" {if $field.required}required{/if}>
          <option value="" disabled {if !$field.value}selected{/if}>{l s='-- vyberte si obec alebo mesto doručenia --' d='Shop.Forms.Labels'}</option>
          {foreach from=$shipping_villages item="village"}
            <option value="{$village.name}" data-zipcode="{$village.zipcode|escape:'html':'UTF-8'}" data-price="{$village.price|escape:'html':'UTF-8'}" {if $field.value eq $village.name} selected {/if}>{$village.name}{if $village.zipcode} ({$village.zipcode}){/if} - {$village.price|floatval} {if isset($currency.iso_code)}{$currency.iso_code}{elseif isset($shipping_currency_iso)}{$shipping_currency_iso}{else}EUR{/if}</option>
          {/foreach}
        </select>
        {include file='_partials/form-errors.tpl' errors=$field.errors}
        {if (!$field.required && !rtrim($field.label))}
          <div class="form-text">{l s='Optional' d='Shop.Forms.Labels'}</div>
        {/if}
      </div>
    {else}
      {$smarty.block.parent}
    {/if}
  {else}
    {$smarty.block.parent}
  {/if}
{/block}

{block name='address_form_url'}
  <form
    method="POST"
    action="{url entity='order' params=['id_address' => $id_address]}"
    data-id-address="{$id_address}"
    data-refresh-url="{url entity='order' params=['ajax' => 1, 'action' => 'addressForm', 'id_address' => $id_address]}"
    data-ps-action="form-validation"
  >
{/block}

{block name='form_fields' append}
  <input type="hidden" name="saveAddress" value="{$type}">

  {if $type === "delivery" && empty($split_address_steps)}
    <div class="mb-3 form-check">
      <input class="form-check-input" name="use_same_address" id="use_same_address" type="checkbox" value="1" {if $use_same_address} checked {/if}>
      <label class="form-check-label" for="use_same_address">{l s='Use this address for invoice too' d='Shop.Theme.Checkout'}</label>
    </div>
  {elseif $type === "invoice" && !empty($split_address_steps) && !empty($has_shipping_address)}
    <div class="mb-3 form-check">
      <input type="hidden" name="same_as_shipping" value="0">
      <input class="form-check-input" name="same_as_shipping" id="same_as_shipping" type="checkbox" value="1" {if $use_same_address} checked {/if}>
      <label class="form-check-label" for="same_as_shipping">{l s='Same as Shipping' mod='viragoscheckout'}</label>
    </div>
  {/if}
{/block}

{block name='form_buttons'}
  {if !$form_has_continue_button}
    <div class="buttons-wrapper buttons-wrapper--split buttons-wrapper--no-fw-mobile buttons-wrapper--invert-mobile mt-3">
      <a class="js-cancel-address btn btn-basic" href="{url entity='order' params=['cancelAddress' => {$type}]}">{l s='Cancel' d='Shop.Theme.Actions'}</a>
      <button type="submit" class="btn btn-primary">{l s='Save' d='Shop.Theme.Actions'}</button>
    </div>
  {else}
    <div class="buttons-wrapper {if $customer.addresses|count == 0}buttons-wrapper--end{else}buttons-wrapper--split buttons-wrapper--no-fw-mobile buttons-wrapper--invert-mobile{/if} mt-3">
      {if $customer.addresses|count > 0}
        <a class="js-cancel-address btn btn-basic" href="{url entity='order' params=['cancelAddress' => {$type}]}">{l s='Cancel' d='Shop.Theme.Actions'}</a>
      {/if}

      <button type="submit" class="btn btn-primary" name="confirm-addresses" value="1" data-ps-action="form-validation-submit">
        {l s='Continue' d='Shop.Theme.Actions'}
        <i class="material-icons rtl-flip" aria-hidden="true">&#xE5C8;</i>
      </button>
    </div>
  {/if}
{/block}
