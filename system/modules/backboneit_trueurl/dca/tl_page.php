<?php

array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'], array('TrueURLBackend', 'onsubmitPage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'], array('TrueURLBackend', 'regeneratePageRoots'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onrestore_callback'], array('TrueURLBackend', 'onrestorePage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onrestore_callback'], array('TrueURLBackend', 'regeneratePageRoots'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncopy_callback'], array('TrueURLBackend', 'oncopyPage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncopy_callback'], array('TrueURLBackend', 'regeneratePageRoots'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncut_callback'], array('TrueURLBackend', 'oncutPage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncut_callback'], array('TrueURLBackend', 'regeneratePageRoots'));

foreach($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'] as &$arrCallback) {
    if($arrCallback[1] == 'generateArticle') {
        $arrCallback[0] = 'TrueURLBackend';
        break;
    }
}

$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_regeneratePageRoots'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_regeneratePageRoots'],
	'href'	=> 'key=bbit_turl_regeneratePageRoots'
);
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_repair'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_repair'],
	'href'	=> 'key=bbit_turl_repair'
);

foreach($GLOBALS['TL_DCA']['tl_page']['palettes'] as $strSelector => &$strPalette) if($strSelector != '__selector__') {
    if($strSelector == 'root') {
        $strPalette = str_replace(',alias', ',alias,bbit_turl_defaultInherit,bbit_turl_childrenInherit', $strPalette);
    } else {
        $strPalette = str_replace(',alias', ',alias,bbit_turl_inherit,bbit_turl_childrenInherit', $strPalette);
    }
}

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']		= 'realurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave']	= true;

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_defaultInherit'] = array(
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_defaultInherit'],
    'inputType'     => 'checkbox',
    'eval' => array(
        'tl_class'          => 'w50 m12 cbx'
    )
);

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_inherit'] = array(
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit'],
    'inputType'     => 'checkbox',
    'eval' => array(
        'tl_class'          => 'w50 m12 cbx'
    )
);

/*
$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_childrenInherit'] = array(
	'label'         => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_childrenInherit'],
	'inputType'     => 'checkbox',
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
