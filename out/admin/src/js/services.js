'use strict';

/* Services module */

angular.module('main.services', [])

/**
 * A simple angular value
 * @type string
 */
.value('version', '0.1.0')

/**
 * Service factory for calling our simple PHP interface
 * @param $http
 * @returns asynchronous "promise" functions
 */
.factory('IolyService', ['$http', function($http) {
  function postFormData(fnc, data){
    return $http({
        method: 'POST',
        url: gOxidSelfLink,
        data: $.param({cl: 'omc_main', fnc: fnc, data: angular.toJson(data)}),
        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}
    });
  }
  return {
    getAllModules: function(searchText, page, pageSize, orderBy, orderDir, onlyInstalled, onlyActive, selectedTags, priceRange){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'getAllModulesAjax',page: page, pageSize: pageSize, orderBy: orderBy, orderDir: orderDir, searchstring: searchText, onlyInstalled: onlyInstalled, onlyActive: onlyActive, selectedTags: JSON.stringify(selectedTags), priceRange: JSON.stringify(priceRange)}});
    },
    downloadModule: function(moduleid, moduleversion){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'downloadModuleAjax',moduleid: moduleid, moduleversion: moduleversion}});
    },
    removeModule: function(moduleid, moduleversion){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'removeModuleAjax',moduleid: moduleid, moduleversion: moduleversion}});
    },
    updateIoly: function(){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'updateIolyAjax'}});
    },
    updateRecipes: function(){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'updateRecipesAjax'}});
    },
    updateConnector: function(){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'downloadModuleAjax',moduleid: moduleid, moduleversion: moduleversion}});
    },
    getContributors: function(){
        return $http.get('https://api.github.com/repos/OXIDprojects/OXID-Module-Connector/contributors');
    },
    getModuleHooks: function(moduleid, moduleversion){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'getModuleHooksAjax',moduleid: moduleid, moduleversion: moduleversion}});
    },
    getAllTags: function(searchText, onlyInstalled, onlyActive, selectedTags, priceRange){
      return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'getAllTagsAjax', searchstring: searchText, onlyInstalled: onlyInstalled, onlyActive: onlyActive, selectedTags: JSON.stringify(selectedTags), priceRange: JSON.stringify(priceRange)}});
    },
    isModuleActive: function(moduleid, moduleversion){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'isModuleActiveAjax',moduleid: moduleid, moduleversion: moduleversion}});
    },
    getActiveModuleSubshops: function(moduleid, moduleversion){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'getActiveModuleSubshopsAjax',moduleid: moduleid, moduleversion: moduleversion}});
    },
    activateModule: function(moduleid, shopids, action, moduleversion){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'activateModuleAjax', moduleid: moduleid, shopids: shopids, action: action, moduleversion: moduleversion}});
    },
    generateViews: function(shopIds){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'generateViewsAjax', shopIds: shopIds}});
    },
    emptyTmp: function(){
        return $http.get(gOxidSelfLink, {params: {cl:'omc_main', isajax:true, fnc:'emptyTmpAjax'}});
    },
    saveFooBar: function(mapping){
        return postFormData('savefoobar', mapping);
    }
  };
}])

;
