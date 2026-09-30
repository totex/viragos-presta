{extends file='parent:checkout/_partials/steps/personal-information.tpl'}

{block name='step_content'}
  {if $guest_allowed && (!$customer.is_logged || $customer.is_guest)}
    <div class="buttons-wrapper buttons-wrapper--end mb-3">
      <button
        class="btn btn-primary"
        type="submit"
        form="customer-form"
        name="continue"
        value="1"
        data-link-action="register-new-customer"
        data-ps-action="form-validation-submit"
      >
        {l s='Continue as guest' d='Shop.Theme.Checkout'}
        <i class="material-icons rtl-flip" aria-hidden="true">&#xE5C8;</i>
      </button>
    </div>
  {/if}

  {$smarty.block.parent}
{/block}