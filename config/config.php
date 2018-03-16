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
 * FRONT END MODULES
 */
$GLOBALS['FE_MOD']['miscellaneous']['avalex_privacy_policy'] = '\numero2\avalex\ModuleAvalexPrivacyPolicy';


/**
 * HOOKS
 */
$GLOBALS['TL_HOOKS']['getSystemMessages'][] = array('\numero2\avalex\AvalexBackend', 'getSystemMessages');


/**
 * CRONJOBS
 */
$GLOBALS['TL_CRON']['hourly'][] = array('\numero2\avalex\AvalexBackend', 'updatePrivacyPolicy');