<?php
/**
 * @package   oxcom-omc
 * @category  OXID Modul Connector
 * @license   MIT License http://opensource.org/licenses/MIT
 * @link      https://github.com/OXIDprojects/OXID-Module-Connector
 * @version   1.0.0
 */
class omc_helper extends oxSuperCfg
{
    /**
     * Return the path to the global lib dir
     * @param string $sModuleId
     * @param string $sVersion
     * @param string $sFileName
     * @return string
     */
    public function getIolyLibPath($sModuleId, $sVersion, $sFileName = '')
    {
        $sFilePath = oxRegistry::getConfig()->getCurrentShopUrl(false).'modules/oxcom/oxcom-omc/libs/' .$sModuleId . '/' . $sVersion;
        if ($sFileName != '') {
            $sFilePath .= '/' . $sFileName;
        }
        return $sFilePath;
    }
    /**
     * Return the path to the ioly module
     * @param string $sFilePath
     * @return string
     */
    public function getIolyPath($sFilePath)
    {
        $sFilePath = oxRegistry::getConfig()->getCurrentShopUrl(false).'modules/oxcom/oxcom-omc/' .$sFilePath;
        return $sFilePath;
    }

    /**
     * Get array of shop ids from a string
     * @param string $sShopIds
     *
     * @return array
     */
    public function getShopIdsFromString($sShopIds)
    {
        $aShopIds = array();
        if ($sShopIds == "all") {
            $aShopIds = oxRegistry::getConfig()->getShopIds();
        } elseif (strpos($sShopIds, ",") !== false) {
            $aShopIds = explode(",", $sShopIds);
        } else {
            // single shopid
            if (trim($sShopIds) != '') {
                $aShopIds[] = $sShopIds;
            }
        }
        return $aShopIds;
    }

    /**
     * Activate a module in one or more shops
     * @param string  $moduleId   The ID of the OXID module
     * @param string  $sShopIds
     * @param boolean $sAction
     * @return array
     */
    public function activateModule($moduleId, $sShopIds, $sAction = "activate")
    {
        $aShopIds = $this->getShopIdsFromString($sShopIds);

        $msg = "";

        $oConfig = oxRegistry::getConfig();
        /**
         * @var oxmodulelist $oModuleList
         */
        $oModuleList = oxNew('oxModuleList');
        $sModulesDir = $oConfig->getModulesDir();
        $aModules = $oModuleList->getModulesFromDir($sModulesDir);

        $headerStatus = "HTTP/1.1 200 Ok";

        // ignore any module output, e.g. activation errors!
        ob_start();
        if (!in_array($moduleId, array_keys($aModules))) {
            $msg .= "Modul nicht gefunden: <b>$moduleId</b><br/>";
        } else {
            $msg .= "Modulhandling <b>$moduleId</b> für Shop-ID <i>" . implode(", ", $aShopIds) . "</i> ...<br/>";
            foreach ($aShopIds as $sShopId) {
                // set shopId
                $oConfig->setShopId($sShopId);

                foreach ($aModules as $sModuleId => $oModule) {
                    if (strtolower($moduleId) != strtolower($sModuleId)) {
                        continue;
                    }
                    /**
                     * @var oxmodule $oModule
                     */
                    if ($sAction == "activate") {
                        $msg .= "Shop-ID $sShopId: Aktiviere $sModuleId ...<br/>";
                        try {
                            if (class_exists('oxModuleInstaller')) {
                                /** @var oxModuleCache $oModuleCache */
                                $oModuleCache = oxNew('oxModuleCache', $oModule);
                                /** @var oxModuleInstaller $oModuleInstaller */
                                $oModuleInstaller = oxNew('oxModuleInstaller', $oModuleCache);

                                if ($oModuleInstaller->activate($oModule)) {
                                    $msg .= "$sModuleId aktiviert!<br/>";
                                } else {
                                    $msg .= "$sModuleId - Fehler beim Aktivieren!<br/>";
                                }
                            } else {
                                if ($oModule->activate()) {
                                    $msg .= "$sModuleId aktiviert!<br/>";
                                } else {
                                    $msg .= "$sModuleId - Fehler beim Aktivieren!<br/>";
                                }
                            }
                        } catch (oxException $oEx) {
                            $msg .= $oEx->getMessage();
                            $headerStatus = "HTTP/1.1 500 Internal Server Error";
                        }
                    } elseif($sAction == "deactivate") { // deactivate!
                        $msg .= "Shop-ID $sShopId: Deaktiviere $sModuleId ...<br/>";
                        try {
                            if (class_exists('oxModuleInstaller')) {
                                /** @var oxModuleCache $oModuleCache */
                                $oModuleCache = oxNew('oxModuleCache', $oModule);
                                /** @var oxModuleInstaller $oModuleInstaller */
                                $oModuleInstaller = oxNew('oxModuleInstaller', $oModuleCache);

                                if ($oModuleInstaller->deactivate($oModule)) {
                                    $msg .= "$sModuleId deaktiviert!<br/>";
                                } else {
                                    $msg .= "$sModuleId - Fehler beim Deaktivieren!<br/>";
                                }
                            } else {
                                if ($oModule->deactivate()) {
                                    $msg .= "$sModuleId deaktiviert!<br/>";
                                } else {
                                    $msg .= "$sModuleId - Fehler beim Deaktivieren!<br/>";
                                }
                            }
                        } catch (oxException $oEx) {
                            $headerStatus = "HTTP/1.1 500 Internal Server Error";
                            $msg .= $oEx->getMessage();
                        }
                    } elseif($sAction == "reactivate") { // reactivate!
                        $msg .= "Shop-ID $sShopId: Deaktiviere $sModuleId ...<br/>";
                        try {
                            if (class_exists('oxModuleInstaller')) {
                                /** @var oxModuleCache $oModuleCache */
                                $oModuleCache = oxNew('oxModuleCache', $oModule);
                                /** @var oxModuleInstaller $oModuleInstaller */
                                $oModuleInstaller = oxNew('oxModuleInstaller', $oModuleCache);

                                if ($oModuleInstaller->deactivate($oModule)) {
                                    $msg .= "$sModuleId deaktiviert!<br/>";
                                } else {
                                    $msg .= "$sModuleId - Fehler beim Deaktivieren!<br/>";
                                }
                            } else {
                                if ($oModule->deactivate()) {
                                    $msg .= "$sModuleId deaktiviert!<br/>";
                                } else {
                                    $msg .= "$sModuleId - Fehler beim Deaktivieren!<br/>";
                                }
                            }
                        } catch (oxException $oEx) {
                            $headerStatus = "HTTP/1.1 500 Internal Server Error";
                            $msg .= $oEx->getMessage();
                        }
                        $msg .= "Shop-ID $sShopId: Aktiviere $sModuleId ...<br/>";
                        try {
                            if (class_exists('oxModuleInstaller')) {
                                /** @var oxModuleCache $oModuleCache */
                                $oModuleCache = oxNew('oxModuleCache', $oModule);
                                /** @var oxModuleInstaller $oModuleInstaller */
                                $oModuleInstaller = oxNew('oxModuleInstaller', $oModuleCache);

                                if ($oModuleInstaller->activate($oModule)) {
                                    $msg .= "$sModuleId aktiviert!<br/>";
                                } else {
                                    $msg .= "$sModuleId - Fehler beim Aktivieren<br/>";
                                }
                            } else {
                                if ($oModule->activate()) {
                                    $msg .= "$sModuleId aktiviert!<br/>";
                                } else {
                                    $msg .= "$sModuleId - Fehler beim Aktivieren!<br/>";
                                }
                            }
                        } catch (oxException $oEx) {
                            $msg .= $oEx->getMessage();
                            $headerStatus = "HTTP/1.1 500 Internal Server Error";
                        }
                    } elseif($sAction == "reset") { // reactivate!
                        try {
                            if (class_exists('oxModuleInstaller')) {
                                /** @var oxModuleCache $oModuleCache */
                                $oModuleCache = oxNew('oxModuleCache', $oModule);
                                $oModuleCache->resetCache();
                                $msg .= "$sModuleId - Modulcache resettet!<br/>";
                            }
                        } catch (oxException $oEx) {
                            $msg .= $oEx->getMessage();
                            $headerStatus = "HTTP/1.1 500 Internal Server Error";
                        }
                    }
                }
            }
        }
        ob_end_clean();
        return array("header" => $headerStatus, "message" => $msg);
    }
    /**
     * Generate views
     * @param array $aShopIds
     * @return array
     */
    public function generateViews($aShopIds)
    {
        if (!is_array($aShopIds)) {
            $aShopIds = $this->getShopIdsFromString($aShopIds);
        }
        $msg = "";
        $oShop = oxNew('oxShop');
        $oShop->generateViews();
        foreach ($aShopIds as $sShopId) {
            $oShop->load($sShopId);
            $msg .= "Views für Shop-ID <i>$sShopId</i> werden aktualisiert ...<br/>";
            $oShop->generateViews();
        }

        $msg .= "<br/>Update erfolgreich!";
        $headerStatus = "HTTP/1.1 200 Ok";
        return array("header" => $headerStatus, "message" => $msg);
    }

    /**
     * Clear tmp dir
     * @return array
     */
    public function emptyTmp()
    {
        $msg = "";
        $tmpdir = oxRegistry::getConfig()->getConfigParam('sCompileDir');
        $d = opendir($tmpdir);
        while (($filename = readdir($d)) !== false) {
            $filepath = $tmpdir . $filename;
            if (is_file($filepath)) {
                $msg .= "$filepath ...<br>";
                unlink($filepath);
            }
        }
        $headerStatus = "HTTP/1.1 200 Ok";
        $msg .= "<br/>Verzeichnis wurde geleert!";
        return array("header" => $headerStatus, "message" => $msg);
    }
}
?>
