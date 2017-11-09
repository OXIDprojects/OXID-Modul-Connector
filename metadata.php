<?php
/**
 * @package   oxcom-omc
 * @category  OXID Modul Connector
 * @license   MIT License http://opensource.org/licenses/MIT
 * @link      https://github.com/OXIDprojects/OXID-Module-Connector
 * @version   1.0.0
 */
 
/**
 * Metadata version
 */
$sMetadataVersion = '1.1';


/**
 * OXID module connector description
 */
$sOmcModuleDesc_de = '
    OXID Modulkatalog mit integrierter Modulinstallation.<br>
    <hr style="margin-bottom: 15px;">
    <a href="https://github.com/OXIDprojects/OXID-Module-Connector" target="_blank">Github</a> &nbsp;|&nbsp; 
    <a href="https://github.com/OXIDprojects/OXID-Module-Connector/blob/module/CHANGELOG.md" target="_blank">Changelog</a> &nbsp;|&nbsp; 
    <a href="https://github.com/OXIDprojects/OXID-Module-Connector/wiki/Support" target="_blank">Support</a> &nbsp;|&nbsp; 
    Aktuellste Modulversion: &nbsp;<img src="https://oxidforge.org/omc/version.png" border="0">
';
$sOmcModuleDesc_en = '
    OXID module catalog with integrated module installation.<br>
    <hr style="margin-bottom: 15px;">
    <a href="https://github.com/OXIDprojects/OXID-Module-Connector" target="_blank">Github</a> &nbsp;|&nbsp; 
    <a href="https://github.com/OXIDprojects/OXID-Module-Connector/blob/module/CHANGELOG.md" target="_blank">Changelog</a> &nbsp;|&nbsp; 
    <a href="https://github.com/OXIDprojects/OXID-Module-Connector/wiki/Support" target="_blank">Support</a> &nbsp;|&nbsp; 
    Latest module version: &nbsp;<img src="https://oxidforge.org/omc/version.png" border="0">
';

/**
 * Module information
 */
$aModule = array(
    'id'          => 'oxcom-omc',
    'title'       => 'OXID Modul Connector',
    'description' => array(
        'de' => $sOmcModuleDesc_de,
        'en' => $sOmcModuleDesc_en,
    ),
    'thumbnail'   => 'out/admin/img/omc_logo_200px.jpg',
    'version'     => '1.1.0',
    'author'      => 'OXID Community',
    'url'         => 'https://github.com/OXIDprojects/OXID-Modul-Connector',
    'extend'      => array(
    ),
    'files'       => array(
        'omc_main'     => 'oxcom/oxcom-omc/controllers/admin/omc_main.php',
        'omc_helper'   => 'oxcom/oxcom-omc/core/omc_helper.php',
    ),
    'templates'   => array(
        'omc_main.tpl'     => 'oxcom/oxcom-omc/views/admin/tpl/omc_main.tpl',
    ),
    'blocks'      => array(
        array(
            'template' => 'headitem.tpl',
            'block' => 'admin_headitem_incjs',
            'file' => '/blocks/admin_headitem_incjs.tpl'
        ),
        array(
            'template' => 'headitem.tpl',
            'block' => 'admin_headitem_js',
            'file' => '/blocks/admin_headitem_js.tpl'
        ),
        array(
            'template' => 'headitem.tpl',
            'block' => 'admin_headitem_inccss',
            'file' => '/blocks/admin_headitem_inccss.tpl'
        ),
    ),
    'events'      => array(
    ),
    'settings'    => array(
        array('group' => 'settings', 'name' => 'omccookbookurl', 'type' => 'aarr',  'value' => array('omc' => 'https://github.com/OXIDprojects/OXID-Modul-Connector/archive/recipes.zip')),
        array('group' => 'settings', 'name' => 'omcautoupdate', 'type' => 'bool',  'value' => false),
        array('group' => 'settings', 'name' => 'omcenableinst', 'type' => 'bool',  'value' => true),
        array('group' => 'settings', 'name' => 'omccheckactive', 'type' => 'bool',  'value' => false),
    )
);
?>
