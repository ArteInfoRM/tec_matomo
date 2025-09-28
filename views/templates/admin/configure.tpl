{*
**
*  2009-2025 Tecnoacquisti.com
*
*  For support feel free to contact us on our website at http://www.arteinformatica.eu
*
*  @author    Arte e Informatica <admin@arteinformatica.eu>
*  @copyright 2009-2025 Arte e Informatica
*  @version   1.0.0
*  @license   One Paid Licence By WebSite Using This Module. No Rent. No Sell. No Share.
*
*}

<style>
	.graph_visits img {
		max-width: 1600px;
		width: 100%;
		height: auto;
	}
</style>

<div class="panel">
	<h3><i class="icon-credit-card"></i> {l s='Statistics tracking with Matomo' mod='tec_matomo'}</h3>
	<p>
		<strong>{l s='Take back control with Matomo – a powerful web analytics platform that gives you 100% data ownership.' mod='tec_matomo'}</strong><br />
		{l s='Don’t damage your reputation with Google Analytics' mod='tec_matomo'}<br />
		{l s='Be in full control with data ownership and privacy protection' mod='tec_matomo'}<br />
	</p>
	<br />
	<p>
		{if $graph_visits != ''}
	<h4><strong>{l s="Visits recorded in the last 30 days" mod='tec_matomo'}</strong></h4>
		{l s="Connection with the api at matomo: ok" mod='tec_matomo'}<br />
	<div class="graph_visits"><img src="{$graph_visits|escape:'html':'UTF-8'}"></div>
		{/if}
	</p>

</div>


