<?php

$GLOBALS['BE_MOD']['design']['page']['realurl_regeneratePageRoots'] = array('RealURLBackend', 'regeneratePageRoots');
$GLOBALS['BE_MOD']['design']['page']['realurl_repair'] = array('RealURLBackend', 'repair');

$GLOBALS['TL_HOOKS']['addCustomRegexp'][]       = array('RealURLBackend', 'addCustomRegexp');
$GLOBALS['TL_HOOKS']['getPageIdFromUrl'][]      = array('RealUrl', 'getPageIdFromUrl');
