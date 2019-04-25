<?php

$arrConfig = &$GLOBALS['TL_DCA']['tl_page']['config'];
foreach(array('oncreate', 'onsubmit', 'onrestore', 'oncopy', 'oncut') as $strCallback) {
	$strKey = $strCallback . '_callback';
	$arrConfig[$strKey] = (array) $arrConfig[$strKey];
	array_unshift($arrConfig[$strKey], array('TrueURLBackend', $strCallback . 'Page'));
}

foreach($arrConfig['onsubmit_callback'] as &$arrCallback) {
	if($arrCallback[1] == 'generateArticle') {
		$arrCallback[0] = 'TrueURLBackend';
		break;
	}
}

$GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'] = $GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'];
$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'] = array('TrueURLBackend', 'labelPage');

$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_alias'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow'],
	'href'	=> 'key=bbit_turl_alias',
	'button_callback'=> array('TrueURLBackend', 'buttonAlias'),
);
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_regenerate'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_regenerate'],
	'href'	=> 'key=bbit_turl_regenerate',
	'button_callback'=> array('TrueURLBackend', 'buttonRegenerate'),
);
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_repair'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_repair'],
	'href'	=> 'key=bbit_turl_repair',
	'button_callback'=> array('TrueURLBackend', 'buttonRepair'),
);
$GLOBALS['TL_DCA']['tl_page']['list']['operations']['bbit_turl_autoInherit'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_autoInherit'],
	'icon'	=> 'system/modules/backboneit_trueurl/html/images/page_link.png',
	'href'	=> 'key=bbit_turl_autoInherit',
	'button_callback'=> array('TrueURLBackend', 'buttonAutoInherit'),
);

foreach($GLOBALS['TL_DCA']['tl_page']['palettes'] as $strSelector => &$strPalette) if($strSelector != '__selector__') {
	if($strSelector == 'root') {
		$strPalette = str_replace(',type', ',type,bbit_turl_rootInheritProxy,bbit_turl_defaultInherit', $strPalette);
	} else {
		$strPalette = str_replace(',type', ',type,bbit_turl_inherit,bbit_turl_transparent,bbit_turl_ignoreRoot', $strPalette);
	}
}

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']		= 'trueurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave']	= true;
array_unshift($GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'], array('TrueURLBackend', 'saveAlias'));
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'][] = array('TrueURLBackend', 'resetFolderUrlConfig');

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_rootInheritProxy'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInherit'],
	'inputType'	=> 'select',
	'options'	=> array('normal', 'always', 'never'),
	'reference' => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions'],
	'eval'		=> array(
		'doNotSaveEmpty'=> true,
		'tl_class'	=> 'clr w50',
	),
	'load_callback' => array(
		array('TrueURLBackend', 'loadRootInherit'),
	),
	'save_callback' => array(
		array('TrueURLBackend', 'saveRootInherit'),
	),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_defaultInherit'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_defaultInherit'],
	'inputType'	=> 'checkbox',
	'eval'		=> array(
		'tl_class'	=> 'w50 cbx m12',
	),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_inherit'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit'],
	'inputType'	=> 'checkbox',
	'eval'		=> array(
		'tl_class'	=> 'clr w50 cbx',
	),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_transparent'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_transparent'],
	'inputType'	=> 'checkbox',
	'eval'		=> array(
		'tl_class'	=> 'w50 cbx',
	),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_ignoreRoot'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_ignoreRoot'],
	'inputType'	=> 'checkbox',
	'eval'		=> array(
		'tl_class'	=> 'clr w50 cbx',
	),
);
