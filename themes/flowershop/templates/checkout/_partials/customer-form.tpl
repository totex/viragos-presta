{extends file='parent:checkout/_partials/customer-form.tpl'}

{block name='form_buttons'}
	{if !$guest_allowed}
		{$smarty.block.parent}
	{/if}
{/block}