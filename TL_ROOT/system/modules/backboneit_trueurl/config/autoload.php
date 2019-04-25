<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2013 Leo Feyer
 *
 * @package Backboneit_trueurl
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'TrueURL'         => 'system/modules/backboneit_trueurl/TrueURL.php',
	'TrueURLBackend'  => 'system/modules/backboneit_trueurl/TrueURLBackend.php',
));
