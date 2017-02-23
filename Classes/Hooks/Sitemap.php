<?php
namespace HENRIKBRAUNE\SeoBasicsPluginSitemap\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Henrik Braune <henrik@braune.org>, HENRIK BRAUNE
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use B13\SeoBasics\Controller\SitemapController;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 *
 *
 * @package seo_basics_plugin_sitemap
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class Sitemap
{

    /**
     * @var TypoScriptFrontendController
     */
    protected $frontendController;

    /**
     * @var DatabaseConnection
     */
    protected $dbConnection;

    public function __construct()
    {
        $this->frontendController = $GLOBALS['TSFE'];
        $this->dbConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * @param array $params
     * @param SitemapController $sitemap
     * @return void
     */
    public function setAdditionalUrls($params, SitemapController $sitemap)
    {

        $plugins = GeneralUtility::removeDotsFromTS($this->frontendController->tmpl->setup['plugin.']['tx_seobasicspluginsitemap.']['extensions.']);

        foreach ($plugins as $plugin => $configuration) {
            //check if we have extName in typoscript or use the configuration root as extension name
            if (isset($configuration['extName']) && $configuration['extName'] != ''){
                $extName = $configuration['extName'];
            } else {
                $extName = $plugin;
            }
            if (ExtensionManagementUtility::isLoaded($extName)) {
                $hreflangs = $configuration['languages'];
                $where = !empty($configuration['where']) ? $configuration['where'] : '';

                $enableFileds = $this->frontendController->cObj->enableFields($configuration['table']);
                $where .= empty($where) ? substr($enableFileds, 4) : $enableFileds;

                $result = $this->dbConnection->exec_SELECTgetRows(
                    implode(',', $configuration['fields']),
                    $configuration['table'],
                    $where
                );

                $additionalParams = [];
                foreach ($configuration['additionalParams'] as $param) {
                    $pair = GeneralUtility::trimExplode('=', $param);
                    $additionalParams[$pair[0]] = $pair[1];
                }

                if (is_array($result)) {
                    foreach ($result as $row) {
                        $uniqueAdditionalParams = [];
                        foreach ($additionalParams as $paramName => $paramValue) {
                            $uniqueAdditionalParams[$paramName] = (substr($paramValue, 0, 1) == '$') ? $row[substr($paramValue, 1)] : $paramValue;
                        }

                        $conf = [
                            'parameter' => $configuration['detailPid'],
                            'additionalParams' => GeneralUtility::implodeArrayForUrl('', $uniqueAdditionalParams),
                            'forceAbsoluteUrl' => 1
                        ];

                        $link = $this->frontendController->cObj->typoLink_URL($conf);

                        if ($row[$configuration['fields']['tstamp']]) {
                            $lastmod = '<lastmod>' . htmlspecialchars(date('c', $row[$configuration['fields']['tstamp']])) . '</lastmod>';
                        } else {
                            $lastmod = '';
                        }

                        $params['content'] .= '<url><loc>' . htmlspecialchars($link) . '</loc>' . $lastmod ;

                        // if langues are added to typoscript configuration
                        if (is_array($hreflangs) && count($hreflangs) > 0) {
                            // check name of field for language parent id
                            if (isset($configuration['fields']['l10n_parent']) && $configuration['fields']['l10n_parent'] != ''){
                                $langParentField = $configuration['fields']['l10n_parent'];
                            } else {
                                $langParentField = 'l10n_parent';
                            }

                            // for all over languages from typoscript configuration
                            foreach($hreflangs as $langKey => $hrefLang){
                                // get configuration for rendering main URL
                                $langConf = $conf;
                                // add language parameter
                                $langConf['additionalParams'] .= '&L=' . $langKey;

                                // for non-default language - check if translated item exists
                                if ($langKey > 0){
                                    // check if [all languages] is not set for current record
                                    $record = $this->dbConnection->exec_SELECTgetSingleRow(
                                        'sys_language_uid',
                                        $configuration['table'],
                                        $configuration['where'] . ' AND uid=' . $row[$configuration['fields']['uid']]
                                    );
                                    // in that case we have an item
                                    if ($record['sys_language_uid'] == -1){
                                        $translationExists = true;
                                    } else {
                                        // the record is not for all languages so we checking for translation
                                        $transItem = $this->dbConnection->exec_SELECTgetSingleRow(
                                            'uid',
                                            $configuration['table'],
                                            $configuration['where'] . ' AND sys_language_uid=' . $langKey . ' AND ' . $langParentField . '=' . $row[$configuration['fields']['uid']] . ' AND hidden=0 AND deleted=0'
                                        );
                                        if (isset($transItem['uid']) && $transItem['uid'] > 0){
                                            $translationExists = true;
                                        } else {
                                            $translationExists = false;
                                        }
                                    }
                                } else {
                                    //for language 0 we always have a link 
                                    $translationExists = true;
                                }

                                // render a URL for translation if it exists
                                if ($translationExists){
                                    $link = $this->frontendController->cObj->typoLink_URL($langConf);    
                                    $params['content'] .= '<xhtml:link rel="alternate" hreflang="' . $hrefLang . '" href="' . htmlspecialchars($link) . '" />';
                                }

                            }
                        }
                        $params['content'] .= '</url>';
                    }
                }
            }
        }
    }
}
