<?php

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow']
	= array('Show page alias', 'Shows the alias of the pages within the listing overview.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasHide']
	= array('Hide page alias', 'Hides the alias of the pages from the listing overview.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_regenerate']
	= array('Regenerate pages', 'Regenerates direct references of all pages to their root pages. This reference is needed by the "backboneit_trueurl" extension to provide an efficient calculation of the frontend page selection algorithmn.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_repair']
	= array('Repair page alias', 'Checks and repairs all page alias, if needed. Additionally empty alias will be filled with a generated one.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_autoInherit']
	= array('Check and enable automatic parent page alias inheritance', 'Enables the automatic parent page alias inheritance for this page and all its descendants, if the page alias starts with the alias of the parent page.');
	
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_defaultInherit']
	= array('Default setting for parent page alias inheritance', 'The default setting for the option "Inherit parent page alias" of newly created page below this root page.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit']
	= array('Inherit parent page alias', 'The parent page alias will be used as a prefix for this page alias. Changes within the parent page alias are reflected within this alias.');

$GLOBALS['TL_LANG']['tl_page']['errNoAlias']
	= 'Alias missing!';
$GLOBALS['TL_LANG']['tl_page']['errNoFragment']
	= 'Alias fragment missing!';
$GLOBALS['TL_LANG']['tl_page']['errInvalidFragment']
	= 'Alias does ends with the fragment set!';
$GLOBALS['TL_LANG']['tl_page']['errInvalidParentAlias']
	= 'Parent page alias missing!';
