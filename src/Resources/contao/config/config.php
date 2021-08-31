<?php

$GLOBALS['BE_MOD']['design']['page']['bbit_turl_alias']			= array('TrueURLBackend', 'keyAlias');
$GLOBALS['BE_MOD']['design']['page']['bbit_turl_regenerate']	= array('TrueURLBackend', 'keyRegenerate');
$GLOBALS['BE_MOD']['design']['page']['bbit_turl_repair']		= array('TrueURLBackend', 'keyRepair');
$GLOBALS['BE_MOD']['design']['page']['bbit_turl_autoInherit']	= array('TrueURLBackend', 'keyAutoInherit');

$GLOBALS['BBIT']['TURL']['unrouteable'][]	= 'error_401';
$GLOBALS['BBIT']['TURL']['unrouteable'][]	= 'error_403';
$GLOBALS['BBIT']['TURL']['unrouteable'][]	= 'error_404';
$GLOBALS['BBIT']['TURL']['unrouteable'][]	= 'folder'; // aschempp's folderpage extension

$GLOBALS['TL_HOOKS']['loadDataContainer'][]		= array('TrueURLBackend', 'hookLoadDataContainer');
$GLOBALS['TL_HOOKS']['addCustomRegexp'][]       = array('TrueURLBackend', 'hookAddCustomRegexp');

