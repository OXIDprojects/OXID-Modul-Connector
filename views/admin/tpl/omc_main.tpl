[{include file="headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

[{if $readonly }]
    [{assign var="readonly" value="readonly disabled"}]
[{else}]
    [{assign var="readonly" value=""}]
[{/if}]

[{if !$allowSharedEdit }]
    [{assign var="disableSharedEdit" value="readonly disabled"}]
[{else}]
    [{assign var="disableSharedEdit" value=""}]
[{/if}]

<form>
    <input type="hidden" name="shopid" id="shopid" value="[{ $shopid }]">
</form>

<div id="magicarea" ng-app="main">
    <div ng-controller="IolyCtrl">
        <script type="text/ng-template" id="my-tags-template">
            <div class="tag-template" ng-class="data.selected ? 'active' : ''">
                <div>
                    <a ng-if="data.selected" class="" ng-click="$removeTag()"><span ng-class="data.selected ? 'active' : ''">{{data.text}}</span></a>
                    <span ng-if="!data.selected" ng-class="data.selected ? 'active' : ''">{{data.text}}</span>
                    [{* <a ng-if="data.selected" class="remove-button" ng-click="$removeTag()">&#10006;</a> *}]
                </div>
            </div>
        </script>

        [{if $iolyerrorfatal ne ''}]
            <h1>[{oxmultilang ident='IOLY_MAIN_TITLE'}]</h1>
            <div class="error alert alert-danger alert-dismissable">
                [{$iolyerrorfatal}]
            </div>
        [{else}]

            <script type="text/ng-template" id="myModalContent.html">
                <div class="modal-header">
                    <h3 class="modal-title">{{headline}}</h3>
                </div>
                <div class="modal-body" ng-bind-html="content">
                    {{content}}
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" ng-click="cancel()">[{oxmultilang ident="IOLY_BUTTON_CANCEL"}]</button>
                    <button class="btn btn-primary" ng-click="ok()">[{oxmultilang ident="IOLY_BUTTON_OK"}]</button>
                </div>
            </script>

            [{* headline *}]
            <div class="row omcHeadline">
                <div class="col-sm-10" style="padding: 0;">
                    <h1>[{oxmultilang ident='IOLY_MAIN_TITLE'}] ({{numRecipes}} [{oxmultilang ident="IOLY_RECIPES"}])</h1>
                    <div style="line-height: 1.5;">
                        [{oxmultilang ident="IOLY_MAIN_INFOTEXT"}]
                        [{oxmultilang ident="IOLY_VERSION_RECIPES_NOTICE"}]
                    </div>
                    <p>&nbsp;</p>
                    <b>[{oxmultilang ident='IOLY_DETAILS_FILTER'}]</b>
                    <br><br>
                </div>
                <div class="col-sm-2" style="padding: 0;">
                    <div class="btn-group dropdown pull-right">
                        <button type="button" class="btn btn-success dropdown-toggle btn-small">
                            <span class="glyphicon glyphicon-th-list"></span>
                        </button>
                        <ul class="dropdown-menu pull-left" role="menu">
                            <li><a href="#" ng-click="updateRecipes()">[{oxmultilang ident='IOLY_RECIPE_UPDATE_BUTTON'}]</a></li>
                            <li><a href="#" ng-click="updateConnector('[{oxmultilang ident='IOLY_CONNECTOR_UPDATE_SUCCESS'}]')">[{oxmultilang ident='IOLY_CONNECTOR_UPDATE_BUTTON'}]</a></li>
                            <li><a href="#" ng-click="updateIoly()">[{oxmultilang ident='IOLY_IOLY_UPDATE_BUTTON'}]</a></li>
                            <li><a href="#" ng-click="generateViews()">[{oxmultilang ident='IOLY_CREATE_VIEWS'}]</a></li>
                            <li><a href="#" ng-click="emptyTmp()">[{oxmultilang ident='IOLY_CLEAR_TEMP'}]</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            [{* error messages *}]
            <div class="row omcHeadline">
                <div class="col-sm-12">
                    <a name="iolyerrors" id="iolyerrors"></a>
                    [{if $iolyerror ne ''}]
                    <div id="iolyerror" class="alert alert-danger alert-dismissable">
                        [{$iolyerror}]
                    </div>
                    [{/if}]

                    [{if $iolymsg ne ''}]
                    <script>
                        setTimeout(function() {
                            document.getElementById('iolymsg').style.display = 'none';
                        }, 4000);
                    </script>
                    <div id="iolymsg" class="alert alert-success alert-dismissable">
                        [{$iolymsg}]
                    </div>
                    [{/if}]
                    <div>
                        <alert ng-repeat="alert in alerts" type="{{alert.type}}" close="closeAlert($index)"><span ng-bind-html="alert.trustedMsg"></span></alert>
                    </div>
                </div>
            </div>

            [{* filter *}]
            <div class="row omcHeadline">
                <div id="filter">
                    <div class="col-sm-6">
                        <div id="tags">
                            <tags-input
                                    on-tag-clicked="filterTag($tag)"
                                    on-tag-removed="tagRemoved($tag)"
                                    ng-model="currentTags"
                                    class="ti-input-sm"
                                    placeholder="-"
                                    template="my-tags-template">
                            </tags-input>
                        </div>
                    </div>
                    <div class="col-sm-1"></div>
                    <div class="col-sm-5">
                        <div>
                            <input type="checkbox" name="onlyInstalled" id="onlyInstalled" value="1" ng-click="refreshTable()"><label for="onlyInstalled" style="font-weight: normal;">&nbsp; [{oxmultilang ident="IOLY_ONLY_INSTALLED"}]</label>
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <input type="checkbox" name="onlyActive" id="onlyActive" value="1" ng-click="refreshTable()"><label for="onlyActive" style="font-weight: normal;">&nbsp; [{oxmultilang ident="IOLY_ONLY_ACTIVE"}]</label>
                            <p>&nbsp;</p>
                            <div id="priceslider">
                                <rzslider rz-slider-model="minRangeSlider.minValue" rz-slider-high="minRangeSlider.maxValue" rz-slider-options="minRangeSlider.options"></rzslider>
                            </div>
                        </div>
                    </div>
                    <div class="clear"></div>
                    <br>
                </div>
            </div>

            <table id="iolyNgTable" ng-table="tableParams" show-filter="true" class="table">
                <tbody>
                <tr ng-repeat="module in $data">
                    <td data-title="'[{oxmultilang ident="IOLY_MODULE_NAME"}]'" sortable="'name'" filter="{ 'name': 'text' }">

                        <div class="moduleBox">
                            <div ng-show="module.installed">
                                <div class="moduleBoxHeaderInstalled"></div>
                            </div>
                            <div ng-hide="module.installed">
                                <div class="moduleBoxHeader"></div>
                            </div>
                            <div class="moduleBoxContent">
                                <div class="moduleBoxContentLeft">
                                    <div class="moduleBoxDesc">
                                        <h2>{{module.name}}</h2>
                                        <div style="color:#ccc; margin: 5px 0 5px 0;">&mdash;</div>
                                        <span>{{module.desc.[{$langabbrev}]}}</span>
                                        <div style="color:#ccc; margin: 5px 0 5px 0;">&mdash;</div>
                                        <b>[{oxmultilang ident='IOLY_VENDOR'}]:</b> {{module.vendor}}
                                        &nbsp;&middot;&nbsp;
                                        <b>[{oxmultilang ident='IOLY_LICENSE'}]:</b> {{module.license}}
                                        &nbsp;&middot;&nbsp;
                                        <b>[{oxmultilang ident='IOLY_PRICE'}]:</b> <span ng-if="module.price == '0.00'">[{oxmultilang ident='IOLY_PRICE_FREE'}]</span><span ng-if="module.price != '0.00'">{{module.price}} &euro;</span>
                                        <br>
                                        <b>[{oxmultilang ident='IOLY_TAGS'}]:</b> <span ng-repeat="tag in module.tags">{{tag}}<span ng-if="!$last">, </span></span>
                                    </div>
                                </div>
                                <div class="moduleBoxContentMiddle">
                                    <div class="moduleBoxInfos">
                                        <span ng-show="module.picture"><br><img src="{{module.picture}}" border="0" alt="{{module.name}}" title="{{module.name}}" class="picture"></span>
                                    </div>
                                </div>
                                <div class="moduleBoxContentRight">
                                    <table style="width: 100%;">
                                        <tr ng-repeat="(key, version) in module.versions">
                                            <td class="iolynoline">
                                                <div class="moduleBoxActions" ng-hide="version.url">
                                                    [{oxmultilang ident='IOLY_DETAILS_NOINSTALL'}]
                                                </div>
                                                <div class="moduleBoxActions" ng-show="version.url">
                                                    <div ng-hide="version.installed">
                                                        <button tooltip-placement="top" tooltip="[{oxmultilang ident='IOLY_INSTALL_MODULE_HINT'}]" type="submit" ng-click="downloadModule(module.packageString, key, '[{oxmultilang ident="IOLY_MODULE_DOWNLOAD_SUCCESS"}]')" class="loadModuleButton btn btn-large" ng-class="{'btn-success': version.matches, 'btn-error' : !version.matches}">[{oxmultilang ident="IOLY_BUTTON_DOWNLOAD_VERSION_1"}] {{key}} [{oxmultilang ident="IOLY_BUTTON_DOWNLOAD_VERSION_2" }]</button>
                                                    </div>
                                                    <div ng-show="version.installed">
                                                        <button tooltip-placement="top" tooltip="[{oxmultilang ident='IOLY_REINSTALL_MODULE_HINT'}]" type="submit" ng-click="downloadModule(module.packageString, key, '[{oxmultilang ident="IOLY_MODULE_DOWNLOAD_SUCCESS"}]')" class="loadModuleButton btn btn-large" ng-class="{'btn-warning': version.matches, 'btn-error' : !version.matches}"><span class="glyphicon glyphicon-repeat"></span> [{oxmultilang ident="IOLY_BUTTON_DOWNLOAD_VERSION_3" }]</button> &nbsp;
                                                        [{if $oView->allowActivation()}]
                                                            <span class="btn-group dropdown">
                                                                <button type="button" tooltip-placement="top" tooltip="[{oxmultilang ident='IOLY_ACTIVATE_MODULE_HINT'}]" class="btn btn-primary dropdown-toggle btn-large ">
                                                                    <span class="glyphicon glyphicon-list"></span> [{oxmultilang ident='IOLY_DROPDOWN_MORE_ACTIONS'}]
                                                                </button>
                                                                <ul class="dropdown-menu" role="menu">
                                                                    <li><a href="#" ng-click="activateModule('activate', module.packageString, key)">[{oxmultilang ident='IOLY_ACTIVATE_MODULE'}]</a></li>
                                                                    <li><a href="#" ng-click="activateModule('deactivate', module.packageString, key)">[{oxmultilang ident='IOLY_DEACTIVATE_MODULE'}]</a></li>
                                                                    <li><a href="#" ng-click="activateModule('reactivate', module.packageString, key)">[{oxmultilang ident='IOLY_REACTIVATE_MODULE'}]</a></li>
                                                                    <li><a href="#" ng-click="activateModule('reset', module.packageString, key)">[{oxmultilang ident='IOLY_RESET_MODULECACHE'}]</a></li>
                                                                </ul>
                                                            </span> &nbsp;
                                                        [{/if}]
                                                        <button tooltip-placement="top" tooltip="[{oxmultilang ident='IOLY_UNINSTALL_MODULE_HINT'}]" type="submit" ng-click="removeModule(module.packageString, key, '[{oxmultilang ident="IOLY_MODULE_UNINSTALL_SUCCESS"}]')" class="loadModuleButton  btn btn-large btn-danger"><span class="glyphicon glyphicon-remove-sign"></span> [{oxmultilang ident="IOLY_BUTTON_REMOVE_VERSION_2"}]</button>
                                                        <br><br>
                                                        <span ng-show="module.installed" style="color: green;">
                                                            <span class="glyphicon glyphicon-ok"></span>&nbsp; [{oxmultilang ident='IOLY_DETAILS_INSTALLED'}] ({{key}})
                                                        </span>
                                                        <span ng-show="module.active" style="color: green;">
                                                            <br><span class="glyphicon glyphicon-ok"></span>&nbsp; [{oxmultilang ident='IOLY_DETAILS_ACTIVE'}]
                                                        </span>
                                                    </div>
                                                </div>
                                                <div ng:repeat="(subkey, versiondata) in version">
                                                    <div ng-switch="subkey">
                                                        <div class="moduleBoxActions" ng-switch-when="project">
                                                            <a href="{{versiondata}}" target="_blank">[{oxmultilang ident="IOLY_PROJECT_URL"}]</a>
                                                        </div>
                                                        <div ng-switch-when="supported">
                                                            [{oxmultilang ident='IOLY_OXID_VERSIONS'}] <span ng-class="{success: oxidversion == '[{ $oView->getShopMainVersion() }]'}" ng:repeat="oxidversion in versiondata">{{oxidversion}}<span ng-if="!$last">&nbsp;&nbsp;</span></span>
                                                        </div>

                                                        <!--<div ng-switch-when="mapping">
                                                            <strong>[{oxmultilang ident="IOLY_OXID_MAPPINGS"}]: </strong>
                                                            <div ng:repeat="mappingdata in versiondata">
                                                                <div ng:repeat="(mappingkey, mappingval) in mappingdata">{{mappingkey}} => {{mappingval}}</div>
                                                            </div>
                                                        </div>-->
                                                    </div>
                                                </div>

                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="clear"></div>
                            </div>
                        </div>


                    </td>
                </tr>
                </tbody>
            </table>
        [{/if}]

        <div id="contributors" class="omcHeadline">
            [{oxmultilang ident="IOLY_DETAILS_CONTRIBUTOR"}]
            <ul id='contributorslist'>
                <li ng-repeat="contributor in contributors | unique: 'login'">
                    <a target='_blank' href='{{contributor.html_url}}'><img ng-src='{{contributor.avatar_url}}' style='border-radius: 100%; width: 20px;' alt='{{contributor.login}}' title='{{contributor.login}}' border='0'/></a>
                </li>
            </ul>
        </div>

    </div>
</div> <!-- /magicarea -->
<hr>

<div id="iolyInfoFooter">
	[{oxmultilang ident="IOLY_VERSION_MODULE"}] <b>[{ $oView->getModuleVersion() }]</b> &mdash; [{oxmultilang ident="IOLY_VERSION_CORE"}] <b>[{ $oView->getIolyCoreVersion() }]</b>
	[{ if $oView->getIolyCookbookVersion()|count > 0  }]
		 &mdash; [{oxmultilang ident="IOLY_VERSION_RECIPES"}]
		[{ foreach key=basketindex from=$oView->getIolyCookbookVersion() name=cookbooks key=cbkey item=cbversion }]
			<b>[{ $cbkey }]</b> ([{ $cbversion }])
			[{ if !$smarty.foreach.cookbooks.last }], [{ /if }]
		[{ /foreach }]
	[{ /if }]
    <br><br>
</div>

[{include file="bottomnaviitem.tpl"}]

[{include file="bottomitem.tpl"}]
