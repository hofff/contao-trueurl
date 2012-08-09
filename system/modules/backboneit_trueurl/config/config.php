<?php

$GLOBALS['BE_MOD']['design']['page']['bbit_turl_regeneratePageRoots'] = array('TrueURLBackend', 'regeneratePageRoots');
$GLOBALS['BE_MOD']['design']['page']['bbit_turl_repair'] = array('TrueURLBackend', 'repair');

$GLOBALS['TL_HOOKS']['addCustomRegexp'][]       = array('TrueURLBackend', 'addCustomRegexp');
$GLOBALS['TL_HOOKS']['getPageIdFromUrl'][]      = array('TrueURLFrontend', 'getPageIdFromUrl');
