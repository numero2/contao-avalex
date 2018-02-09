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
 * Add palettes to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['avalex_privacy_policy'] = '{title_legend},name,headline,type;{config_legend},avalex_apikey;{template_legend:hide},customTpl;{expert_legend:hide},guests,cssID,space';


/**
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['avalex_apikey'] = array(
    'label'               => &$GLOBALS['TL_LANG']['tl_module']['avalex_apikey']
,   'inputType'           => 'text'
,   'eval'                => array('mandatory'=>true, 'tl_class'=>'w50')
,   'sql'                 => "varchar(255) NOT NULL default ''"
);