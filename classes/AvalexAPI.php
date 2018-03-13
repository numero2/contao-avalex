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


class AvalexAPI {


    /**
     * API Endpoint
     * @var string
     */
    const API_ENDPOINT = 'https://beta.avalex.de';


    /**
     * API Key
     * @var string
     */
    private $apiKey = NULL;


    /**
     * Constructor
     *
     * @param $apiKey
     * @return numero2/AvalexAPI
     */
    public function __construct( $apiKey=NULL ) {

        if( !empty($apiKey) ) {
            $this->apiKey = $apiKey;
        } else {
            throw new \Exception("No avalex API key given");
        }
    }


    /**
     * Checks if the current api key is valid / configure
     *
     * @return boolean
     */
    public function isConfigured() {

        $response = $this->send('/api_keys/is_configured.json');

        if( !empty($response) ) {
            return true;
        }

        return false;
    }


    /**
     * Checks if there is a newer version available since last retrieval
     *
     * @param  int $lastChecked Unix timestamp of last retrieval
     * @return bool
     */
    public function checkVersion( $lastChecked ) {

        $date = new \DateTime();
        $date->setTimestamp($lastChecked);

        $response = $this->send('/api_keys/check_version.json', array(
            'last_checked' => $date->format("Y-m-d H:i:s")
        ));

        if( $response ) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Returns the privacy policy HTML
     *
     * @return String
     */
    public function getPrivacyPolicy() {

        // find frontend module matching api key
        $oModules = NULL;
        $oModules = \ModuleModel::findOneBy( array('avalex_apikey=?'), array($this->apiKey) );

        if( $oModules ) {

            $oCache = new \stdClass;
            $oCache = json_decode($oModules->avalex_cache);

            // if empty or older than 6 hours force update of cache
            $updateCache = (empty($oCache) || empty($oCache->content) || (time() - $oCache->date) > (3600*6)) ? true : false;

            // check if there is a newer version available to force an update
            // do this check only if cached version is older than 10 minutes
            if( !$updateCache || (time() - $oCache->date) > 600 ) {

                if( $this->checkVersion($oCache->date) ) {
                    $updateCache = true;
                }
            }

            if( $updateCache ) {

                $content = $this->send('/datenschutzerklaerung');

                if( !empty($content) ) {

                    $oCache->date = time();
                    $oCache->content = $content;

                    $oModules->avalex_cache = json_encode($oCache);
                    $oModules->save();

                } else {

                    \Message::addError(
                        sprintf(
                            $GLOBALS['TL_LANG']['avalex']['msg']['update_failed']
                        ,   \Date::parse(\Config::get('datimFormat'), time())
                        )
                    ,   'BE'
                    );
                }
            }

            return $oCache->content;
        }

        return null;
    }


    /**
     * Send request to the API
     *
     * @param  String $uri
     * @param  array  $aParams
     * @return String
     */
    private function send( $uri=NULL, $aParams=array() ) {

        $url  = self::API_ENDPOINT.$uri.'?apikey='.$this->apiKey;

        if( !empty($aParams) ) {
            $url .= '&'.http_build_query($aParams);
        }

        try {

            // Contao 4 and above
            if( class_exists('\GuzzleHttp\Client') ) {

                $request = new \GuzzleHttp\Client(
                    [
                        \GuzzleHttp\RequestOptions::TIMEOUT         => 5
                    ,   \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 5
                    ,   \GuzzleHttp\RequestOptions::HTTP_ERRORS     => false
                    ]
                );

                $response = $request->get($url);

                if( $response->getStatusCode() != 200 ) {
                    return false;
                } else {
                    return $response->getBody()->getContents();
                }

            // Contao 3
            } else {

                $oRequest = NULL;
                $oRequest = new \Request();

                $oRequest->send($url);

                if( $oRequest->code != 200 ) {
                    return false;
                } else {
                    $responseBody = $oRequest->response;
                }
            }

        } catch( \Exception $e ) {

            \System::log('Exception while retrieving data from avalex (' . $e->getMessage() . ')', __METHOD__, TL_ERROR);

            return false;
        }
    }
}