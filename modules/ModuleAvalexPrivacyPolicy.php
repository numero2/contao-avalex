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


class ModuleAvalexPrivacyPolicy extends \Module {


    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_avalex_privacy_policy';


    /**
     * API Endpoint
     * @var string
     */
    const URL = 'https://clicklegal.azurewebsites.net/datenschutzerklaerung';


    /**
     * Display a wildcard in the back end
     * @return string
     */
    public function generate() {

        if( TL_MODE == 'BE' ) {

            $objTemplate = new \BackendTemplate('be_wildcard');

            if (class_exists('\Patchwork\Utf8')) {
                $objTemplate->wildcard = '### '.\Patchwork\Utf8::strtoupper($GLOBALS['TL_LANG']['FMD']['avalex_privacy_policy'][0]).' ###';
            } else {
                $objTemplate->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['avalex_privacy_policy'][0]).' ###';
            }

            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }


    /**
     * Generate module
     */
    protected function compile() {

        $this->Template = new \FrontendTemplate(empty($this->customTpl)?$this->strTemplate:$this->customTpl);

        try {

            $url  = self::URL.'?apikey='.$this->avalex_apikey;

            // Contao 4 and above
            if( class_exists('\GuzzleHttp\Client') ) {

                $request = new \GuzzleHttp\Client(
                    [
                        \GuzzleHttp\RequestOptions::TIMEOUT         => 5,
                        \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 5,
                        \GuzzleHttp\RequestOptions::HTTP_ERRORS     => false,
                    ]
                );
                $response = $request->get($url);

                if( $response->getStatusCode() != 200 ) {
                    throw new \RuntimeException($response->getReasonPhrase());
                }

                $responseBody = $response->getBody()->getContents();


            // Contao 3
            } else {

                $oRequest = NULL;
                $oRequest = new \Request();

                $oRequest->send($url);

                if( $oRequest->code != 200 ) {

                    if( $oRequest->hasError ) {
                        throw new \RuntimeException($oRequest->error);
                    } else {
                        throw new \RuntimeException("Not responding with status code 200, received: ".$oRequest->code );
                    }
                }

                $responseBody = $oRequest->response;
            }

            if( !empty($responseBody) ) {
                $this->Template->content = $responseBody;
            }

        } catch( \Exception $e ) {

            \System::log('Exception while retrieving data from avalex (' . $e->getMessage() . ')', __METHOD__, TL_ERROR);
        }
    }
}
