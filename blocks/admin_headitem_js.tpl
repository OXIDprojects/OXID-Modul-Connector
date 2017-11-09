[{$smarty.block.parent}]
[{if $oViewConf->getActiveClassName() eq "omc_main"}]
<script>
    var gModuleBasePath = "[{$oViewConf->getModuleUrl('oxcom-omc', '')}]";
    var gOxidSelfLink = "[{$oViewConf->getSelfLink()}]";
    gOxidSelfLink = gOxidSelfLink.replace(/&amp;/g, '&');
</script>

[{/if}]