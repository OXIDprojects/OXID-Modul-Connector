<?php
/**
 * @package   oxcom-omc
 * @category  OXID Modul Connector
 * @license   MIT License http://opensource.org/licenses/MIT
 * @link      https://github.com/OXIDprojects/OXID-Module-Connector
 * @version   1.0.0
 */
class omc_main extends oxAdminView
{
    /**
     * Current class template name.
     * @var string
     */
    protected $_sThisTemplate = 'omc_main.tpl';
    /**
     * URL to ioly core (ioly.php)
     * @var string
     */
    protected $_iolyCoreUrl = "https://raw.githubusercontent.com/ioly/ioly/core/ioly.php";

    protected $_iolyCore = null;
    protected $_authFile = null;
    /**
     * The ioly core
     * @var ioly\ioly
     */
    protected $_ioly = null;
    protected $_iolyHelper = null;
    protected $_allModules = null;
    protected $_allTags = array();
    protected $_currSortKey = '';
    protected $_aModulesInDir = null;
    /**
     * Filter ioly modules for OXID
     * @var array
     */
    protected $_iolyFilter = array('type' => 'oxid');

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_iolyCore = getShopBasePath() . '/modules/oxcom/oxcom-omc/ioly.php';
        $this->_authFile = getShopBasePath() . '/modules/oxcom/oxcom-omc/.auth';
        if ($this->_ioly === null) {
            $this->_initIoly();
        }
    }

    /**
     * Gets ioly core if it doesn't exist.
     * @return boolean
     */
    protected function _initIoly()
    {
        if (!file_exists($this->_iolyCore)) {
            if (!$this->updateIoly()) {
                $this->addTplParam("iolyerrorfatal", oxRegistry::getLang()->translateString('IOLY_EXCEPTION_CORE_NOT_LOADED'));
            }
        }
        if (file_exists($this->_iolyCore)) {
            include_once $this->_iolyCore;
            $this->_ioly = new ioly\ioly(true);
            $this->_ioly->setSystemBasePath(oxRegistry::getConfig()->getConfigParam('sShopDir'));
            $this->_ioly->setSystemVersion($this->getShopMainVersion());
            $this->_setCookbooks(false);
            return true;
        }
        return false;
    }

    /**
     * Get defined cookbooks array from OXID backend settings
     * @return bool|mixed
     */
    protected function _getCookbooks()
    {
        if (($aCookbookUrls = oxRegistry::getConfig()->getConfigParam('omccookbookurl')) && is_array($aCookbookUrls)) {
            return $aCookbookUrls;
        }
        return false;
    }
    /**
     * Set multiple cookbooks as defined in module settings.
     * @param bool $forceDownload Force zip download?
     * @return null
     */
    protected function _setCookbooks($forceDownload = false)
    {
        if (($aCookbookUrls = $this->_getCookbooks())) {
            $this->_ioly->resetCookbooks();
            // and download new ones....
            $this->_ioly->setCookbooks($aCookbookUrls, $forceDownload);
            return true;
        }
        return false;
    }

    /**
     * Allow module activation via ioly?
     * @return bool
     */
    public function allowActivation()
    {
        return oxRegistry::getConfig()->getShopConfVar('omcenableinst');
    }

    /**
     * Get modules
     */
    public function getAllModules()
    {
        if ($this->_allModules === null) {
            $searchString = oxRegistry::getConfig()->getRequestParameter('searchstring');
            if ($searchString != '') {
                $allModules = $this->_ioly->search($searchString, $this->_iolyFilter);
                $this->addTplParam('searchstring', $searchString);
            } else {
                $allModules = $this->_ioly->listAll($this->_iolyFilter);
            }
            $this->_allModules = $allModules;
        }
    }

    /**
     * Returns all modules as JSON
     */
    public function getAllModulesAjax()
    {
        $page = oxRegistry::getConfig()->getRequestParameter('page');
        if (!$page && $page !== '0') {
            $page = 0;
        } else {
            $page = (int)$page;
        }
        $pageSize = (int)oxRegistry::getConfig()->getRequestParameter('pageSize');
        if (!$pageSize) {
            $pageSize = 20;
        } else {
            $pageSize = (int)$pageSize;
        }
        $offset = $page * $pageSize;
        $this->_currSortKey = oxRegistry::getConfig()->getRequestParameter('orderBy');
        $orderDir = oxRegistry::getConfig()->getRequestParameter('orderDir');
        // fill internal variable
        $this->getAllModules();
        $numItems = 0;
        $data = array();
        if ($this->_allModules && is_array($this->_allModules)) {

            // filter module list
            $onlyInstalled = (oxRegistry::getConfig()->getRequestParameter('onlyInstalled') == "true" || oxRegistry::getConfig()->getRequestParameter('onlyInstalled') == "1" ) ? true : false;
            $onlyActive = (oxRegistry::getConfig()->getRequestParameter('onlyActive') == "true" || oxRegistry::getConfig()->getRequestParameter('onlyActive') == "1") ? true : false;
            $selectedTags = json_decode(oxRegistry::getConfig()->getRequestParameter('selectedTags'));
            $priceRange = json_decode(oxRegistry::getConfig()->getRequestParameter('priceRange'));
            $this->_filterModules($onlyInstalled, $onlyActive, $selectedTags, $priceRange);

            $numItems = count($this->_allModules);
            // sort by requested field
            if ($this->_currSortKey != '') {
                uasort($this->_allModules, array($this, 'cmpModules'));
            }
            // reverse order?
            if ($orderDir == "desc") {
                $this->_allModules = array_reverse($this->_allModules);
            }
            $data = array_slice($this->_allModules, $offset, $pageSize, false);
            // check if module is active?
            if (oxRegistry::getConfig()->getShopConfVar('omccheckactive')) {
                foreach ($data as $idx => $aPackage) {
                    $aVersions = $aPackage['versions'];
                    $packageId = $aPackage['packageString'];
                    foreach ($aVersions as $packageVersion => $versionInfo) {
                        // if not even installed yet, continue!
                        if (!$this->_ioly->isInstalledInVersion($packageId, $packageVersion)) {
                            continue;
                        }
                        if ($this->isModuleActive($packageId, $packageVersion)) {
                            $aPackage['active'] = true;
                        }
                    }
                    $data[$idx] = $aPackage;
                }
            }
            $headerStatus = "HTTP/1.1 200 Ok";
        }
        $res = array(
            'numObjects' => $numItems,
            'result' => $data,
        );
        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     * Filter modules
     * @param boolean $onlyInstalled Only show installed modules
     * @param boolean $onlyActive    Only show installed and active modules
     * @param array   $selectedTags  Tags to filter
     * @param object  $priceRange    Price range (from - to)
     */
    protected function _filterModules($onlyInstalled = false, $onlyActive = true, $selectedTags = array(), $priceRange)
    {
        if ($onlyActive) {
            $onlyInstalled = true;
        }
        foreach ($this->_allModules as $idx => $aPackage) {
            $aVersions = $aPackage['versions'];
            $packageId = $aPackage['packageString'];
            $addModule = false;
            foreach ($aVersions as $packageVersion => $versionInfo) {
                if ($this->_ioly->isInstalledInVersion($packageId, $packageVersion)) {
                    $addModule = true;
                    if ($onlyActive) {
                        if ($this->isModuleActive($packageId, $packageVersion)) {
                            $addModule = true;
                            $aPackage['active'] = true;
                        } else {
                            $addModule = false;
                        }
                    }
                } else {
                    if (!$onlyInstalled) {
                        $addModule = true;
                    }
                }
            }
            // filter by tags
            $tagsFound = 0;
            foreach ($selectedTags as $tag) {
                if (in_array($tag, array_map('strtolower', $aPackage['tags']))) {
                    $tagsFound++;
                }
            }
            if ($tagsFound < count($selectedTags)) {
                $addModule = false;
            }
            // filter by price
            $dPrice = floatval($aPackage['price']);
            if ($dPrice >= $priceRange->from && $dPrice <= $priceRange->to) {
                $inRange = true;
            } else {
                $inRange = false;
            }
            if (!$inRange) {
                $addModule = false;
            }

            // add module?
            if ($addModule) {
                $this->_allModules[$idx] = $aPackage;
            } else {
                unset($this->_allModules[$idx]);
            }
        }
    }

    /**
     * Comparator function to sort modules by name
     * @param string $a
     * @param string $b
     * @return int
     */
    public function cmpModules($a, $b)
    {
        if (strtolower($a[$this->_currSortKey]) == strtolower($b[$this->_currSortKey])) {
            return 0;
        }
        return (strtolower($a[$this->_currSortKey]) < strtolower($b[$this->_currSortKey])) ? -1 : 1;
    }

    /**
     * Update ioly via AJAX
     */
    public function updateIolyAjax()
    {
        if (!$this->updateIoly()) {
            $msg = oxRegistry::getLang()->translateString('IOLY_IOLY_UPDATE_ERROR');
            $headerStatus = "HTTP/1.1 500 Internal Server Error";
            $res = array("status" => 500, "message" => $msg);
        } else {
            $msg = oxRegistry::getLang()->translateString('IOLY_IOLY_UPDATE_SUCCESS');
            $headerStatus = "HTTP/1.1 200 Ok";
            $res = array("status" => $msg);
        }
        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     * Update ioly via AJAX
     */
    public function updateRecipesAjax()
    {
        try {
            $this->_setCookbooks(true);
            $msg = oxRegistry::getLang()->translateString('IOLY_RECIPE_UPDATE_SUCCESS');
            $headerStatus = "HTTP/1.1 200 Ok";
            $res = array("status" => $msg);
        } catch (Exception $ex) {
            $msg = oxRegistry::getLang()->translateString('IOLY_RECIPE_UPDATE_ERROR') . $ex->getMessage();
            $headerStatus = "HTTP/1.1 500 Internal Server Error";
            $res = array("status" => 500, "message" => $msg);
        }
        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     *
     * @param type $download_size
     * @param type $downloaded_size
     * @param type $upload_size
     * @param type $uploaded_size
     */
    public static function getCurlStatus($download_size, $downloaded_size, $upload_size, $uploaded_size)
    {
        static $previousProgress = 0;
        if ($download_size == 0) {
            $progress = 0;
        } else {
            $progress = round($downloaded_size * 100 / $download_size);
        }
        if ($progress > $previousProgress) {
            $aStatus = array("progress" => $progress, "download_size" => $download_size, "downloaded_size" => $downloaded_size);
            oxRegistry::getSession()->setVariable('iolyDownloadStatus', $aStatus);
        }
    }

    /**
     * Read CURL download status from session via AJAX
     */
    public function getCurlStatusAjax()
    {
        $aStatus = oxRegistry::getSession()->getVariable('iolyDownloadStatus');
        if ($aStatus && $aStatus != '') {
            $headerStatus = "HTTP/1.1 200 Ok";
            $res = array("status" => $aStatus);
            $this->_returnJsonResponse($headerStatus, $res);
        }
    }

    /**
     * Read "hooks" data from module package
     * to get preinstall and postinstall hooks
     */
    public function getModuleHooksAjax()
    {
        $moduleId = strtolower(urldecode(oxRegistry::getConfig()->getRequestParameter('moduleid')));
        $moduleVersion = oxRegistry::getConfig()->getRequestParameter('moduleversion');
        try {
            $value = $this->_ioly->getJsonValueFromPackage($moduleId, $moduleVersion, "hooks");
            $headerStatus = "HTTP/1.1 200 Ok";
            $res = array("status" => $value);
        } catch (Exception $ex) {
            $headerStatus = "HTTP/1.1 500 Internal Server Error";
            $res = array("status" => 500, "message" => $this->_getIolyErrorMsg($ex));
        }
        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     * Read "tags" data from module package
     */
    public function getModuleTagsAjax()
    {
        $moduleId = strtolower(urldecode(oxRegistry::getConfig()->getRequestParameter('moduleid')));
        $moduleVersion = oxRegistry::getConfig()->getRequestParameter('moduleversion');
        try {
            $value = $this->_ioly->getJsonValueFromPackage($moduleId, $moduleVersion, "tags");
            $headerStatus = "HTTP/1.1 200 Ok";
            $res = array("status" => $value);
        } catch (Exception $ex) {
            $headerStatus = "HTTP/1.1 500 Internal Server Error";
            $res = array("status" => 500, "message" => $this->_getIolyErrorMsg($ex));
        }
        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     * Get all "tags" from all modules
     */
    public function getAllTagsAjax()
    {
        try {
            // fill internal variable
            $this->getAllModules();
            if ($this->_allModules && is_array($this->_allModules)) {

                // filter module list
                $onlyInstalled = (oxRegistry::getConfig()->getRequestParameter('onlyInstalled') == "true" || oxRegistry::getConfig()->getRequestParameter('onlyInstalled') == "1") ? true : false;
                $onlyActive = (oxRegistry::getConfig()->getRequestParameter('onlyActive') == "true" || oxRegistry::getConfig()->getRequestParameter('onlyActive') == "1") ? true : false;
                $selectedTags = json_decode(oxRegistry::getConfig()->getRequestParameter('selectedTags'));
                $priceRange = json_decode(oxRegistry::getConfig()->getRequestParameter('priceRange'));
                $this->_filterModules($onlyInstalled, $onlyActive, $selectedTags, $priceRange);

                foreach ($this->_allModules as $idx => $aPackage) {
                    // save tags of remaining modules
                    $this->_allTags = array_merge($this->_allTags, array_map('strtolower', array_map('strtolower', array_values($aPackage['tags']))));
                }
                $headerStatus = "HTTP/1.1 200 Ok";
                $this->_allTags = array_values(array_unique($this->_allTags));
                sort($this->_allTags);
                //oxRegistry::getUtils()->writeToLog("\nTAGS: " . print_r($this->_allTags, true), "ioly_debug.txt");
                $res = array("status" => $this->_allTags);
            }
        } catch (Exception $ex) {
            $headerStatus = "HTTP/1.1 500 Internal Server Error";
            $res = array("status" => 500, "message" => $this->_getIolyErrorMsg($ex));
        }

        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     * Download module via AJAX
     */
    public function downloadModuleAjax()
    {
        // reset status
        $moduleId = strtolower(urldecode(oxRegistry::getConfig()->getRequestParameter('moduleid')));
        $moduleVersion = oxRegistry::getConfig()->getRequestParameter('moduleversion');
        try {
            $this->_ioly->setCurlCallback(array($this, "getCurlStatus"));
            oxRegistry::getSession()->deleteVariable('iolyDownloadStatus');
            $success = $this->_ioly->install($moduleId, $moduleVersion);
            $headerStatus = "HTTP/1.1 200 Ok";
            $res = array("status" => $success);
        } catch (Exception $ex) {
            $headerStatus = "HTTP/1.1 500 Internal Server Error";
            oxRegistry::getUtils()->writeToLog("\nDownload status: " . $ex->getMessage(), "ioly_debug.txt");
            $res = array("status" => 500, "message" => $this->_getIolyErrorMsg($ex));
        }
        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     *
     * @param Exception $ex
     * @return string
     */
    protected function _getIolyErrorMsg($ex)
    {
        if (get_class($ex) == "ioly\Exception") {
            $sLangCode = "IOLY_EXCEPTION_MESSAGE_CODE_" . $ex->getCode();
            if (($sLang = oxRegistry::getLang()->translateString($sLangCode)) != $sLangCode) {
                $sMsg = $sLang;
            } else {
                $sMsg = $ex->getMessage();
            }
            $aData = $ex->getExtraData();
            if (count($aData)) {
                $sMsg .= " " . implode("\n", $aData);
            }
            return $sMsg;
        }
        return $ex->getMessage();
    }

    /**
     * Get subshop IDs where the module is active
     */
    public function getActiveModuleSubshopsAjax()
    {
        $moduleId = oxRegistry::getConfig()->getRequestParameter("moduleid");
        $moduleVersion = oxRegistry::getConfig()->getRequestParameter("moduleversion");
        if (strpos($moduleId, "/") !== false) {
            $moduleId = $this->getModuleOxid($moduleId, $moduleVersion);
        }
        $aSubshopsActive = $this->getActiveModuleSubshops($moduleId);
        $headerStatus = "HTTP/1.1 200 Ok";
        $res = array("status" => "ok", "subshops" => $aSubshopsActive);
        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     * Get subshop IDs where the module is active
     * @param string $moduleOxid The module OXID
     *
     * @return array
     */
    public function getActiveModuleSubshops($moduleOxid)
    {
        $aSubshopsActive = array();
        $oConfig = oxRegistry::getConfig();
        $aShopIds = oxRegistry::getConfig()->getShopIds();
        if ($this->_aModulesInDir === null) {
            /**
             * @var oxmodulelist $oModuleList
             */
            $oModuleList = oxNew('oxModuleList');
            $sModulesDir = $oConfig->getModulesDir();
            $this->_aModulesInDir = $oModuleList->getModulesFromDir($sModulesDir);

        }
        if (in_array($moduleOxid, array_keys($this->_aModulesInDir))) {
            foreach ($aShopIds as $sShopId) {
                // set shopId
                $oConfig->setShopId($sShopId);
                /**
                 * @var oxmodule $oModule
                 */
                $oModule = oxNew('oxModule');
                if ($oModule->load($moduleOxid) && $oModule->isActive()) {
                    $aSubshopsActive[] = $sShopId;
                }
            }
        }
        return $aSubshopsActive;
    }

    /**
     * Activate a module in one or more shops
     */
    public function activateModuleAjax()
    {
        $helper = $this->getIolyHelper();
        $sShopIds = oxRegistry::getConfig()->getRequestParameter("shopids");
        $moduleId = oxRegistry::getConfig()->getRequestParameter("moduleid");
        $moduleVersion = oxRegistry::getConfig()->getRequestParameter("moduleversion");
        $sAction = oxRegistry::getConfig()->getRequestParameter("action");
        if (strpos($moduleId, "/") !== false) {
            $moduleId = $this->getModuleOxid($moduleId, $moduleVersion);
        }
        $aRet = $helper->activateModule($moduleId, $sShopIds, $sAction);
        $res = array("status" => $aRet['message']);
        $this->_returnJsonResponse($aRet['header'], $res);
    }

    /**
     * Generate views
     */
    public function generateViewsAjax()
    {
        $sShopIds = oxRegistry::getConfig()->getRequestParameter("shopIds");
        $helper = $this->getIolyHelper();
        $aRet = $helper->generateViews($sShopIds);
        $res = array("status" => $aRet['message']);
        $this->_returnJsonResponse($aRet['header'], $res);
    }

    /**
     * Clear tmp dir
     */
    public function emptyTmpAjax()
    {
        $helper = $this->getIolyHelper();
        $aRet = $helper->emptyTmp();
        $res = array("status" => $aRet['message']);
        $this->_returnJsonResponse($aRet['header'], $res);
    }

    /**
     * Uninstall module via AJAX
     */
    public function removeModuleAjax()
    {
        $moduleId = strtolower(urldecode(oxRegistry::getConfig()->getRequestParameter('moduleid')));
        $moduleVersion = oxRegistry::getConfig()->getRequestParameter('moduleversion');
        try {
            // check if the module is still activated!
            $isStillActive = $this->isModuleActive($moduleId, $moduleVersion);
            if (!$isStillActive) {
                $success = $this->_ioly->uninstall($moduleId, $moduleVersion);
                $headerStatus = "HTTP/1.1 200 Ok";
                $res = array("status" => $success);
            } else {
                $headerStatus = "HTTP/1.1 412 Precondition Failed";
                $res = array("status" => 412, "message" => oxRegistry::getLang()->translateString('IOLY_EXCEPTION_MESSAGE_MODULE_ACTIVE'));
            }
        } catch (Exception $ex) {
            $headerStatus = "HTTP/1.1 500 Internal Server Error";
            $res = array("status" => 500, "message" => $this->_getIolyErrorMsg($ex));
        }
        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     * Check if an installed module is still active in the shop
     * and should not be removed!
     * @param string $moduleId
     * @param string $moduleVersion
     * @return boolean
     */
    public function isModuleActive($moduleId, $moduleVersion)
    {
        $moduleOxid = $this->getModuleOxid($moduleId, $moduleVersion);
        return (count($this->getActiveModuleSubshops($moduleOxid)) > 0) ? true : false;
    }

    /**
     * Check if an installed module is still active in the shop
     * and should not be removed!
     */
    public function isModuleActiveAjax()
    {
        $moduleId = oxRegistry::getConfig()->getRequestParameter("moduleid");
        $moduleVersion = oxRegistry::getConfig()->getRequestParameter("moduleversion");
        $headerStatus = "HTTP/1.1 200 Ok";
        $active = $this->isModuleActive($moduleId, $moduleVersion);
        $res = array("status" => 1, "active" => $active);
        $this->_returnJsonResponse($headerStatus, $res);
    }

    /**
     * Get OXID ID of an installed module
     * Reads metadata.php since we don't have that info in the JSON files.
     * @param string $moduleId
     * @param string $moduleVersion
     * @return string
     */
    public function getModuleOxid($moduleId, $moduleVersion)
    {
        // check if there is a module id in the json file...
        if (($moduleId = $this->_ioly->getJsonValueFromPackage($moduleId, $moduleVersion, "id")) != '' && is_array($moduleId)) {
            return $moduleId[0];
        }
        // if not, read the metadata.php file to get the id ...
        $aFiles = $this->_ioly->getFileList($moduleId, $moduleVersion);
        if ($aFiles && is_array($aFiles)) {
            foreach (array_keys($aFiles) as $filePath) {
                if (strpos($filePath, "/metadata.php") !== false && strpos($filePath, "/metadata.php.") === false) {
                    $metaPath = oxRegistry::getConfig()->getConfigParam('sShopDir') . $filePath;
                    if (file_exists($metaPath)) {
                        include $metaPath;
                        return $aModule['id'];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Return JSON and exit
     * @param string $headerStatus
     * @param array  $res
     */
    protected function _returnJsonResponse($headerStatus, $res)
    {
        header("Content-Type: application/json");
        header($headerStatus);
        echo json_encode($res);
        die();
    }

    /**
     * Return our helper class for the view
     * @return omc_helper
     */
    public function getIolyHelper()
    {
        if ($this->_iolyHelper === null) {
            $this->_iolyHelper = oxRegistry::get('omc_helper');
        }
        return $this->_iolyHelper;
    }

    /**
     * Get session id
     * @return string
     */
    public function getSessionId()
    {
        if (($oSession = oxRegistry::getSession()) != null) {
            return $oSession->getId();
        }
        return "";
    }

    /**
     * Get session token
     * @return string
     */
    public function getSessionChallengeToken()
    {
        return oxRegistry::getSession()->getSessionChallengeToken();
    }

    /**
     * Return the shop version string
     * @return string
     */
    public function getShopMainVersion()
    {
        $aVersion = explode(".", oxRegistry::getConfig()->getVersion());
        return $aVersion[0].".".$aVersion[1];
    }

    /**
     * Update ioly lib
     * @return boolean
     */
    public function updateIoly()
    {
        $core = $this->_iolyCoreUrl;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $core);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $data = curl_exec($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $response = array($data, intVal($responseCode));
        if ($data && @file_put_contents($this->_iolyCore, $data)) {
            return $response;
        }
        return false;
    }

    /**
     * Gets ioly OXID connector version
     *
     * @return string
     */
    public function getModuleVersion()
    {
        $sMetaDataPath = oxRegistry::getConfig()->getConfigParam("sShopDir") . "modules/oxcom/oxcom-omc/metadata.php";
        include $sMetaDataPath;
        return $aModule["version"];
    }

    /**
     * Gets ioly core version
     *
     * @return string
     */
    public function getIolyCoreVersion()
    {
        return $this->_ioly->getCoreVersion();
    }

    /**
     * Gets ioly cookbooks version
     *
     * @return string
     */
    public function getIolyCookbookVersion()
    {
        return $this->_ioly->getCookbookVersion();
    }

    /**
     * Auto-uodate everything on load?
     */
    public function iolyAutoUpdate()
    {
        $message_success = $message_error = '';
        if (oxRegistry::getConfig()->getConfigParam('omcautoupdate') == true) {
            if ($this->updateIoly()) {
                $message_success = oxRegistry::getLang()->translateString('IOLY_IOLY_UPDATE_SUCCESS') . '<br>';
            } else {
                $message_error = oxRegistry::getLang()->translateString('IOLY_EXCEPTION_CORE_NOT_LOADED') . '<br>';
            }

            if ($this->_setCookbooks()) {
                $message_success .= oxRegistry::getLang()->translateString('IOLY_RECIPE_UPDATE_SUCCESS') . '<br>';
            } else {
                $message_error .= oxRegistry::getLang()->translateString('IOLY_EXCEPTION_CORE_NOT_LOADED') . '<br>';
            }

            try {
                $this->_ioly->setCurlCallback(array($this, "getCurlStatus"));
                oxRegistry::getSession()->deleteVariable('iolyDownloadStatus');
                $success = $this->_ioly->install("oxcom/oxcom-omc", "latest");
                $message_success .= oxRegistry::getLang()->translateString('IOLY_CONNECTOR_UPDATE_SUCCESS') . '<br>';
                $res = array("status" => $success);
            } catch (Exception $ex) {
                $message_error .= oxRegistry::getLang()->translateString('IOLY_CONNECTOR_UPDATE_ERROR') . '<br>' . $this->_getIolyErrorMsg($ex) . '<br>';
            }

            $this->addTplParam("iolymsg", $message_success);
            $this->addTplParam("iolyerror", $message_error);
        }
    }

    /**
     * Executes parent method parent::render(), passes shop configuration parameters
     * to Smarty and returns name of template file "shop_config.tpl".
     *
     * @return string
     */
    public function render()
    {
        // add curr language abbrevation to the template
        $iLang = oxRegistry::getLang()->getTplLanguage();
        $sLang = oxRegistry::getLang()->getLanguageAbbr($iLang);
        $this->addTplParam("langabbrev", $sLang);
        // ajax call?
        $isAjax = oxRegistry::getConfig()->getRequestParameter('isajax');
        if ($isAjax) {
            die("ajax");
        }
        parent::render();

        $this->iolyAutoUpdate();

        return $this->_sThisTemplate;
    }

    /**
     * Internal helper function to modify all JSON files
     */
    private function writeModifiedJsonFiles()
    {
        // fill internal variable
        $this->getAllModules();
        if ($this->_allModules && is_array($this->_allModules)) {
            foreach ($this->_allModules as $idx => $aPackage) {
                // temp. - write JSON files with moduleid
                while (list($key, $val) = each($aPackage['versions'])) {
                    $firstVersion = $key;
                    break;
                }
                $folderFilename = explode("/", $aPackage['packageString']);
                $sModuleId = $this->getModuleOxid($aPackage['packageString'], $firstVersion);
                $aPackage['moduleId'] = $sModuleId;
                unset($aPackage['installed']);
                unset($aPackage['_cookbook']);
                unset($aPackage['_filename']);
                unset($aPackage['packageString']);
                $sModuleJson = json_encode($aPackage, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                $sFolder = "/jsons/" . $folderFilename[0];
                $sFile = $folderFilename[1] . ".json";
                $baseDir = oxRegistry::getConfig()->getConfigParam('sShopDir');
                exec("mkdir -p " . $baseDir . $sFolder);
                file_put_contents($baseDir . $sFolder . "/" . $sFile, $sModuleJson);
            }
        }
    }
}
