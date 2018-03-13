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

        if( !empty($this->avalex_apikey) ) {

            $oAPI = NULL;
            $oAPI = new AvalexAPI( $this->avalex_apikey );

            $policy = $oAPI->getPrivacyPolicy();

            $this->Template->content = $policy;
        }
    }
}
