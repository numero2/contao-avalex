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

            return '<p class="tl_info">'.$msg.'</p>';
        }
    }
}