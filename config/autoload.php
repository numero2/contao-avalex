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
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
    'numero2\avalex',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
    // Modules
    'numero2\avalex\ModuleAvalexPrivacyPolicy'     => 'system/modules/avalex/modules/ModuleAvalexPrivacyPolicy.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
    'mod_avalex_privacy_policy'         => 'system/modules/avalex/templates/modules',
));
