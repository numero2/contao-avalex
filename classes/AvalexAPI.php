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
    const API_ENDPOINT = 'https://avalex.de';
    const API_ENDPOINT_FALLBACK = 'https://proxy.avalex.de';


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

        if( $response instanceof \stdClass ) {

            return sprintf(
                $GLOBALS['TL_LANG']['avalex']['msg']['key_invalid']
            ,   $response->data
            );

        } else {

            $res = json_decode($response);

            // check if domains match
            if( !empty($res->domain) ) {

                $domain = parse_url($res->domain, PHP_URL_HOST);
                $domain = !$domain?$res->domain:$domain;

                // check current domain
                if( stripos( \Environment::get('host'), $domain ) === FALSE && stripos( $domain, \Environment::get('host')  ) === FALSE ) {

                    // check if we have any root page with matching domain
                    $oPages = NULL;
                    $oPages = \PageModel::findOneBy( array('type=?','dns=?'), array('root',$domain) );

                    if( $oPages ) {

                        return true;

                    } else {

                        return sprintf(
                            $GLOBALS['TL_LANG']['avalex']['msg']['key_invalid_domain']
                        ,   $domain
                        );
                    }
                }
            }

            return true;
        }
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

        if( $response instanceof \stdClass ) {
            return false;
        } else {
            return true;
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

            /*
            // currently disabled because checkVersion reports false data
            if( !$updateCache || (time() - $oCache->date) > 600 ) {

                if( $this->checkVersion($oCache->date) === true ) {
                    $updateCache = true;
                }
            }
            */

            if( $updateCache ) {

                $response = $this->send('/datenschutzerklaerung');

                if( !$response instanceof \stdClass ) {

                    $oCache->date = time();
                    $oCache->content = $response;

                    $oModules->avalex_cache = json_encode($oCache);
                    $oModules->save();

                } else {

                    \System::log(
                        sprintf(
                            'Error while retrieving data from avalex (%s)'
                        ,   $response->code . ' ' . $response->data
                        )
                    ,   __METHOD__
                    ,   TL_ERROR
                    );

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
    private function send( $uri=NULL, $aParams=[], $useFallback=false ) {

        $url  = ($useFallback ? self::API_ENDPOINT_FALLBACK : self::API_ENDPOINT) . $uri . '?apikey=' . $this->apiKey;

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

                try {

                    $response = $request->get($url);

                } catch( \Exception $e ) {

                    // use fallback domain if the main one does not work
                    if( !$useFallback ) {
                        return $this->send($uri, $aParams, true);
                    }

                    throw $e;
                }

                if( $response->getStatusCode() != 200 ) {

                    $message = json_decode( $response->getBody()->getContents() );

                    $return = new \stdClass;
                    $return->code = $response->getStatusCode();
                    $return->data = $message->message ? $message->message : $response->getReasonPhrase();

                    return $return;

                } else {
                    return $response->getBody()->getContents();
                }

            // Contao 3
            } else {

                $oRequest = NULL;
                $oRequest = new \Request();

                $oRequest->send($url);

                if( $oRequest->error && strpos($oRequest->error, '110') !== FALSE ) {

                    // use fallback domain if the main one does not work
                    if( !$useFallback ) {
                        return $this->send($uri, $aParams, true);
                    }
                }

                if( $oRequest->code != 200 ) {

                    $message = json_decode( $oRequest->response );

                    $return = new \stdClass;
                    $return->code =$oRequest->code;
                    $return->data = $message->message ? $message->message : $oRequest->error;

                    return $return;

                } else {

                    return $oRequest->response;
                }
            }

        } catch( \Exception $e ) {

            \System::log('Exception while retrieving data from avalex (' . $e->getMessage() . ')', __METHOD__, TL_ERROR);
            return false;
        }
    }
}