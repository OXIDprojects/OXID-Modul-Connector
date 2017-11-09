var app = angular.module('main', ['ngTable', 'main.services','main.filters','ui.bootstrap', 'angular-loading-bar', 'ngTagsInput', 'rzModule']).
        controller('IolyCtrl', function ($scope, ngTableParams, IolyService, $modal, $document, $timeout, $location, $anchorScroll, $sce) {

            // Register a body reference to use later
            var bodyRef = angular.element( $document[0].body );
            $scope.numRecipes = 0;
            /**
             * Nice modal messsages
             */
            $scope.content = '';
            $scope.headline = '';
            $scope.showMsg = function (headline, content, callback) {
                // Add our overflow hidden class on opening
                // to prevent from scrollbar / page flickering
                bodyRef.addClass('ovh');
                $scope.headline = headline;
                $scope.content = content;
                var modalInstance = $modal.open({
                    templateUrl: 'myModalContent.html',
                    controller: 'ModalInstanceCtrl',
                    size: 'sm',
                    resolve: {
                        content: function () {
                            return $scope.content;
                        },
                        headline: function () {
                            return $scope.headline;
                        }
                    }
                });
                modalInstance.result.then(function () {
                    // Remove it on closing
                    //bodyRef.removeClass('ovh');
                    if(typeof callback !== "undefined") {
                        callback($scope.content);
                    }
                }, function () {
                    // Remove it on dismissal
                    //bodyRef.removeClass('ovh');
                });
            };
            /**
             * Alerts
             */
            $scope.alerts = [];
            $scope.addAlert = function (type, msg, timeout) {
                if(timeout === '' || typeof timeout === "undefined") {
                    timeout = 10000;
                }
                $scope.alerts.push({type: type, msg: msg, trustedMsg: $sce.trustAsHtml(msg)});
                $location.hash('iolyerrors');
                $anchorScroll();
                // set timeout to remove message auto-magically :)
                $timeout(function(){
                    $scope.closeAlert(0);
                }, timeout);
            };
            $scope.closeAlert = function (index) {
                $scope.alerts.splice(index, 1);
            };
            /**
             * 
             * Update the core ioly lib
             */
            $scope.updateIoly = function () {
                var responsePromise = IolyService.updateIoly();

                responsePromise.then(function (response) {
                    $scope.showMsg("Update ioly Core", response.data.status);
                }, function (error) {
                    console.error(error);
                    $scope.addAlert('danger', error.data.message);
                });
            };
            
            /**
             * Read contributors list from Github
             */
            $scope.getContributors = function() {
                var responsePromise = IolyService.getContributors();

                responsePromise.then(function (response) {
                    $scope.contributors = response.data;
                }, function (error) {
                    console.error(error);
                    $scope.addAlert('danger', error.data.message);
                });
            };
            // call it right "on load"...
            $scope.getContributors();

            $scope.isOpen = false;
            $scope.selectedTags = [];
            $scope.priceRange = {};
            $scope.priceRange.from = 0.0;
            $scope.priceRange.to = 5000.0;
            //Range slider config
            $scope.minRangeSlider = {
                minValue: 0,
                maxValue: 4999.99,
                options: {
                    floor: 0,
                    ceil: 5000,
                    step: 25,
                    translate: function (value) {
                        return value + ' €';
                    },
                    id: 'priceslider',
                    onEnd: function(sliderId, modelValue, highValue, pointerType) {
                        console.log("END " + modelValue + " - " + highValue);
                        $scope.priceRange.from = modelValue;
                        $scope.priceRange.to = highValue;
                        $scope.refreshTable();
                    }
                }
            };
            $scope.$watch('isOpen', function() {
                if ($scope.isOpen) {
                    $scope.$broadcast('rzSliderForceRender'); //Force refresh sliders on render. Otherwise bullets are aligned at left side.
                }
            }, true);
            /**
             * TAGS
             */
            $scope.currentTags = [];

            /**
             * Read tags from backend
             */
            $scope.getTags = function(searchText, onlyInstalled, onlyActive, selectedTags, priceRange) {
                var responsePromise = IolyService.getAllTags(searchText, onlyInstalled, onlyActive, selectedTags, priceRange);
                $scope.currentTags = [];
                responsePromise.then(function (response) {
                    if (response !== null && response.data !== null && typeof response.data.status !== "undefined") {
                        angular.forEach(response.data.status, function(value, key) {
                            $scope.currentTags.push({"idx": key, "text": value, "selected": $scope.isSelected(value)});
                        });
                    }
                }, function (error) {
                    console.error(error);
                    $scope.addAlert('danger', error.data.message);
                });
            };
            $scope.isSelected = function(value) {
                if ($scope.selectedTags.indexOf(value) > -1) return 1;
                return 0;
            };

            /**
             * Filter by tags
             * @param tag
             */
            $scope.filterTag = function(tag) {
                $scope.selectedTags.push(tag.text);
                $scope.refreshTable();
            };
            $scope.tagRemoved = function(tag) {
                // remove from array
                $scope.selectedTags = $scope.selectedTags.filter(function(e) { return e !== tag.text })
                $scope.refreshTable();
            };

            /**
             * 
             * Update the recipes db
             */
            $scope.updateRecipes = function () {
                var responsePromise = IolyService.updateRecipes();

                responsePromise.then(function (response) {
                    $scope.showMsg("Update OXID Modulkatalog", response.data.status);
                    // reload ng-table, too
                    $scope.refreshTable();
                }, function (error) {
                    console.error(error);
                    $scope.addAlert('danger', error.data.message);
                });
            };
            /**
             * 
             * Update the ioly oxid connector
             */
            $scope.updateConnector = function (successtext) {
                var responsePromise = IolyService.downloadModule("oxcom/oxcom-omc", "latest", '');

                responsePromise.then(function (response) {
                    $scope.showMsg("Update OXID Modul Connector", successtext);
                    // reload ng-table, too
                    $scope.refreshTable();
                }, function (error) {
                    console.error(error);
                    $scope.addAlert('danger', error.data.message);
                });
            };
            /**
             * 
             * @param {type} page
             * @returns {undefined}
             */
            $scope.refreshTable = function () {
                var currPage = $scope.tableParams.$params.page;
                $scope.tableParams.reload();
                $scope.tableParams.$params.page = currPage;
            };

            /**
             * Uninstall a module
             * @param string packageString
             * @param string moduleversion
             * @param string successtext
             */
            $scope.removeModule = function (packageString, moduleversion, successtext) {
                console.log("removing module " + packageString + ", version: " + moduleversion);
                var responsePromise = IolyService.removeModule(packageString, moduleversion);

                responsePromise.then(function (response) {
                    $scope.showMsg("Modul deinstallieren", successtext);
                    // reload ng-table, too
                    $scope.refreshTable();
                }, function (error) {
                    console.error(error);
                    $scope.addAlert('danger', error.data.message);
                });
            };

            /**
             * Download a module
             * @param string packageString
             * @param string moduleversion
             * @param string successtext
             */
            $scope.downloadModule = function (packageString, moduleversion, successtext) {
                console.log("loading module " + packageString + ", version: " + moduleversion);
                // first check for pre- and postinstall hooks
                var preInstall, postInstall, msg;
                var hooksPromise = IolyService.getModuleHooks(packageString, moduleversion);
                hooksPromise.then(function (response) {
                    // check for hook data
                    if(typeof response.data.status[0] !== "undefined") {
                        preInstall = response.data.status[0].preinstall;
                        postInstall = response.data.status[0].postinstall;
                        msg = '';
                        if(preInstall && typeof preInstall.message !== "undefined") {
                            msg = msg + preInstall.message;
                        }
                        // add link?
                        if(preInstall && typeof  preInstall.link !== "undefined") {
                            msg = msg + " <a href='" + preInstall.link + "' target='_blank'>"+preInstall.link+"</a>";
                        }
                        // custom hook message?
                        if(msg !== '') {
                            if(typeof  preInstall.type === "undefined" ||  preInstall.type === "alert") {
                                // add alert
                                $scope.addAlert('success', msg, 20000);
                            }
                            else {
                                // add overlay
                                $scope.showMsg("Modul-Download", msg);
                            }
                        }
                    }
                    // now start the download
                    var responsePromise = IolyService.downloadModule(packageString, moduleversion);
                    // and wait for response...
                    responsePromise.then(function (response) {
                        // in case of success, check postinstall hook data...
                        msg = '';
                        if(postInstall && typeof postInstall.message !== "undefined") {
                            msg = msg + postInstall.message;
                        }
                        // add link?
                        if(postInstall && typeof  postInstall.link !== "undefined") {
                            msg = msg + " <a href='" + postInstall.link + "' target='_blank'>"+postInstall.link+"</a>";
                        }
                        // custom hook message?
                        if(msg !== '') {
                            if(typeof  postInstall.type === "undefined" ||  postInstall.type === "overlay") {
                                // default: add overlay
                                $scope.showMsg("Information", msg);
                            }
                            else {
                                // add alert
                                $scope.addAlert('success', msg, 20000);
                            }
                        }
                        else {
                            // show default text only
                            $scope.showMsg("Information", successtext);
                        }
                        // reload ng-table, too
                        $scope.refreshTable();
                    }, function (error) {
                        console.error(error);
                        $scope.addAlert('danger', error.data.message);
                    });

                }, function (error) {
                    console.error(error);
                    $scope.addAlert('danger', error.data.message);
                });

            };

        /**
         * Activate module
         */
        $scope.activateModule = function (action, moduleid, moduleVersion) {
            var headline = "Modul aktivieren: ";
            var sDeact = "activate";
            var sAction = "aktiviert";
            var responsePromise = IolyService.getActiveModuleSubshops(moduleid, moduleVersion);
            responsePromise.then(function (response) {
                var minput = "<input type='hidden' name='moduleid' id='moduleid' value='"+moduleid+"'><input type='hidden' name='moduleversion' id='moduleversion' value='"+moduleVersion+"'>";
                if(action == "deactivate") {
                    sDeact = "deactivate";
                    sAction = "deaktiviert";
                    headline = "Modul deaktivieren: ";
                }
                if(action == "reactivate") {
                    sDeact = "reactivate";
                    sAction = "reaktiviert";
                    headline = "Modul reaktivieren: ";
                }
                if(action == "reset") {
                    sDeact = "reset";
                    sAction = "resettet";
                    headline = "Modulcache resetten: ";
                }
                minput += "<br/><input type='hidden' name='action' id='action' value='"+sDeact+"'></div>";
                minput += "<div id='shopsact'>Für welche Shop-IDs soll das Modul "+sAction+" werden?<br></div>"; // Verfügbare Shop-IDs: <i>"+response.data.subshops.toString()+"</i>
                minput += "<div id='shops'><br>CE/PE: <i>all</i><br>EE (alle Subshops): <i>all</i><br>EE (bestimmte Subshops): <i>1,2,5</i><br><br><input type='text' name='shopids' id='shopids' value='all'></div>";
                $scope.showMsg(headline + " " + moduleid, minput, $scope.submitActivateModule);
            }, function (error) {
                console.error(error);
                $scope.addAlert('danger', error.data.status);
            });
        };
        $scope.submitActivateModule = function(content) {
            var moduleId = $document[0].querySelector('#moduleid').value;
            var moduleVersion = $document[0].querySelector('#moduleversion').value;
            var action = $document[0].querySelector('#action').value;
            var shopIds = $document[0].querySelector('#shopids').value;
            var responsePromise = IolyService.activateModule(moduleId, shopIds, action, moduleVersion);

            responsePromise.then(function (response) {
                var shopId = $document[0].querySelector('#shopid').value;
                $scope.showMsg("Modulemanagement", response.data.status);
                // reload shop navi
                top.oxid.admin.reloadNavigation(shopId);
                // reload ng-table, too
                $scope.refreshTable();
            }, function (error) {
                console.error(error);
                $scope.addAlert('danger', error.data.status);
            });
        };
        /**
         * Generate views
         */
        $scope.generateViews = function () {
            var minput = "<div id='shops'>Für welche Shop-IDs sollen die Views aktualisiert werden?<br><br>CE/PE: <i>all</i><br>EE (alle Subshops): <i>all</i><br>EE (bestimmte Subshops): <i>1,2,5</i><br><br><input type='text' name='shopids' id='shopids' value='all'></div>";
            $scope.showMsg("Datenkbank-Views", minput, $scope.submitGenerateViews);
        };
        $scope.submitGenerateViews = function(content) {
            var shopIds = $document[0].querySelector('#shopids').value;
            console.log("Views wurden für folgende Shop-IDs aktualisiert: " + shopIds);
            var responsePromise = IolyService.generateViews(shopIds);

            responsePromise.then(function (response) {
                $scope.showMsg("Datenkbank-Views", response.data.status);
            }, function (error) {
                console.error(error);
                $scope.addAlert('danger', error.data.message);
            });
        };
        /**
         * Clear tmp
         */
        $scope.emptyTmp = function () {
            console.log("Clearing tmp folder ...");
            var responsePromise = IolyService.emptyTmp();

            responsePromise.then(function (response) {
                $scope.showMsg("TMP-Verzeichnis", response.data.status);
            }, function (error) {
                console.error(error);
                $scope.addAlert('danger', error.data.message);
            });
        };

            /**
             * ng-table settings
             * 
             */
            $scope.tableParams = new ngTableParams({
                page: 1,
                count: 10,
                sorting: {
                    name: 'asc'
                }
            }, {
                total: 0,
                getData: function ($defer, params) {
                    var sorting = params.sorting();
                    var filter = params.filter();
                    var sortString = '';
                    var sortDir = '';
                    var searchText = '';
                    for(var s in sorting) {
                        sortString = s;
                        sortDir = sorting[s];
                    }
                    for(var f in filter) {
                        searchText = filter[f];
                    }
                    if (typeof searchText === "undefined" || searchText === "undefined") {
                        searchText = '';
                    }
                    var onlyInstalled = document.getElementById('onlyInstalled').checked;
                    var onlyActive = document.getElementById('onlyActive').checked;
                    var responsePromise = IolyService.getAllModules(searchText, params.page() - 1, params.count(), sortString, sortDir, onlyInstalled, onlyActive, $scope.selectedTags, $scope.priceRange);
                    responsePromise.then(function (response) {
                        params.total(response.data.numObjects);
                        var data = response.data.result;
                        $defer.resolve(data);
                        console.log("table data received: " + response.data.numObjects);
                        $scope.numRecipes = response.data.numObjects;
                        $scope.getTags(searchText, onlyInstalled, onlyActive, $scope.selectedTags, $scope.priceRange);
                    }, function (error) {
                        $scope.addAlert('error', error.data + " (Error " + error.status + ")");
                    });
                }
            });
        })
        /**
         * Modal controller
         */
        .controller('ModalInstanceCtrl', function ($scope, $modalInstance, content, headline, $sce) {
            $scope.content = $sce.trustAsHtml(content);
            $scope.headline = $sce.trustAsHtml(headline);
            $scope.ok = function () {
                $modalInstance.close();
            };
            $scope.cancel = function () {
                $modalInstance.dismiss('cancel');
            };
        })
;



