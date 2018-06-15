<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2018 Leo Feyer
 *
 * @package   avalex
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   LGPL
 * @copyright 2018 numero2 - Agentur für digitales Marketing GbR
 */


/**
 * Namespace
 */
namespace numero2\avalex;


class AvalexBackend {


    /**
     * Checks if the current api key is valid / configure
     *
     * @return boolean
     */
    public function getSystemMessages() {

        $aMessages = [];

        // check module update
        $moduleUpdate = false;
        $moduleUpdate = $this->checkForModuleUpdate();

        if( $moduleUpdate ) {

            $msg = sprintf(
                $GLOBALS['TL_LANG']['avalex']['msg']['module_update']
            ,   $moduleUpdate
            );

            $aMessages[] = '<p class="tl_error">'.$msg.'</p>';
        }

        // find frontend module
        $oModules = NULL;
        $oModules = \ModuleModel::findOneByType('avalex_privacy_policy');

        if( $oModules && $oModules->avalex_cache ) {

            $oCache = NULL;
            $oCache = json_decode($oModules->avalex_cache);

            $msg = sprintf(
                $GLOBALS['TL_LANG']['avalex']['msg']['last_update']
            ,   \Date::parse(\Config::get('datimFormat'), $oCache->date)
            );

            $aMessages[] = '<p class="tl_info">'.$msg.'</p>';
        }

        return implode('',$aMessages);
    }


    /**
     * Updates the privacy policies if necessary
     *
     * @return boolean
     */
    public function updatePrivacyPolicy() {

        // find frontend module
        $oModules = NULL;
        $oModules = \ModuleModel::findByType('avalex_privacy_policy');

        if( $oModules ) {

            while( $oModules->next() ) {

                try {

                    $oAPI = NULL;
                    $oAPI = new \numero2\avalex\AvalexAPI( $oModules->avalex_apikey );

                    $oAPI->getPrivacyPolicy();

                } catch ( \Exception $e ) {

                }
            }
        }
    }


    /**
     * Checks if there is a newer version of the avalex module available
     *
     * @return String
     */
    private function checkForModuleUpdate() {

        #$this->updateLastModuleVersion();

        $currentVersion = NULL;
        $currentVersion = file_get_contents( __DIR__.'/../version.txt' );

        $latestVersion = \Config::get('avalexLatestVersion');

        if( $latestVersion && version_compare($currentVersion, $latestVersion, '<') ) {
            return $latestVersion;
        }

        return false;
    }


    /**
     * Gets the lates version number from GitHub
     * and stores it in config
     *
     * @return none
     */
    public function updateLastModuleVersion() {

        $versionURI = 'https://github.com/numero2/contao-avalex/raw/master/version.txt';
        $latestVersion = NULL;

        // Contao 4 and above
        if( class_exists('\GuzzleHttp\Client') ) {

            $request = new \GuzzleHttp\Client(
                [
                    \GuzzleHttp\RequestOptions::TIMEOUT         => 5
                ,   \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 5
                ,   \GuzzleHttp\RequestOptions::HTTP_ERRORS     => false
                ]
            );

            try {

                $response = $request->get($versionURI);

                if( $response->getStatusCode() == 200 ) {
                    $latestVersion = trim($response->getBody()->getContents());
                }

            } catch( \Exception $e ) {
            }


        // Contao 3
        } else {

            $oRequest = NULL;
            $oRequest = new \Request();

            try {

                $oRequest->redirect = true;

            } catch( \Exception $e ) {

                // older version, maybe 3.1 cannot handle redirects automatically
                $oRequest->send($versionURI);

                if( $oRequest->code == 302 ) {

                    if( !empty($oRequest->headers['Location']) ) {
                        $versionURI = $oRequest->headers['Location'];
                    }
                }
            }

            $oRequest->send($versionURI);

            if( $oRequest->code == 200 ) {

                $latestVersion = trim($oRequest->response);
            }
        }

        if( $latestVersion ) {

            $oConfig = NULL;
            $oConfig = \Config::getInstance();

            if( method_exists($oConfig, 'persist') ) {

                \Config::persist('avalexLatestVersion', $latestVersion);

            } else {

                $strKey = "\$GLOBALS['TL_CONFIG']['avalexLatestVersion']";
                $oConfig->add($strKey, $latestVersion);
            }
        }
    }
}