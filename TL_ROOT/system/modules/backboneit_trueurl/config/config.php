<?php

$GLOBALS['BE_MOD']['design']['page']['bbit_turl_alias']			= array('TrueURLBackend', 'keyAlias');
$GLOBALS['BE_MOD']['design']['page']['bbit_turl_regenerate']	= array('TrueURLBackend', 'keyRegenerate');
$GLOBALS['BE_MOD']['design']['page']['bbit_turl_repair']		= array('TrueURLBackend', 'keyRepair');
$GLOBALS['BE_MOD']['design']['page']['bbit_turl_autoInherit']	= array('TrueURLBackend', 'keyAutoInherit');

$GLOBALS['TL_HOOKS']['loadDataContainer'][]		= array('TrueURLBackend', 'loadDataContainer');
$GLOBALS['TL_HOOKS']['addCustomRegexp'][]       = array('TrueURLBackend', 'addCustomRegexp');
$GLOBALS['TL_HOOKS']['getPageIdFromUrl'][]      = array('TrueURLFrontend', 'getPageIdFromUrl');
