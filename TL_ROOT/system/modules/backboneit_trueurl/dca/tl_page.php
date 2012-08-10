<?php

$arrConfig = &$GLOBALS['TL_DCA']['tl_page']['config'];
foreach(array('onsubmit', 'onrestore', 'oncopy', 'oncut') as $strCallback) {
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

$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_regenerate'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_regenerate'],
	'href'	=> 'key=bbit_turl_regenerate',
);
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_repair'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_repair'],
	'href'	=> 'key=bbit_turl_repair',
);

foreach($GLOBALS['TL_DCA']['tl_page']['palettes'] as $strSelector => &$strPalette) if($strSelector != '__selector__') {
	if($strSelector == 'root') {
		$strPalette = str_replace(',type', ',type,bbit_turl_defaultInherit,bbit_turl_childrenInherit', $strPalette);
	} else {
		$strPalette = str_replace(',alias', ',alias,bbit_turl_inherit,bbit_turl_childrenInherit', $strPalette);
	}
}

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']		= 'trueurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave']	= true;

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_defaultInherit'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_defaultInherit'],
	'inputType'	=> 'checkbox',
	'eval'		=> array(
		'tl_class'	=> 'clr',
	),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_inherit'] = array(
	'label'		=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit'],
	'inputType'	=> 'checkbox',
	'eval'		=> array(
		'tl_class'	=> 'w50 m12 cbx',
	),
);

/*
$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_childrenInherit'] = array(
	'label'		 => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_childrenInherit'],
	'inputType'	 => 'checkbox',
	'options_callback' => array('TrueURLBackend', 'getChildrenInheritOptions'),
	'eval' => array(
		'multiple'	=> true,
		'tl_class'	=> 'clr'
	),
	'load_callback' => array(
		array('TrueURLBackend', 'loadChildrenInherit')
	),
	'save_callback' => array(
		array('TrueURLBackend', 'saveChildrenInherit')
	),
);
*/
