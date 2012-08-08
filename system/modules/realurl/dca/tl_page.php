<?php

array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'], array('RealURLBackend', 'onsubmitPage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'], array('RealURLBackend', 'regeneratePageRoots'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onrestore_callback'], array('RealURLBackend', 'onrestorePage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onrestore_callback'], array('RealURLBackend', 'regeneratePageRoots'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncopy_callback'], array('RealURLBackend', 'oncopyPage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncopy_callback'], array('RealURLBackend', 'regeneratePageRoots'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncut_callback'], array('RealURLBackend', 'oncutPage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncut_callback'], array('RealURLBackend', 'regeneratePageRoots'));

foreach($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'] as &$arrCallback) {
    if($arrCallback[1] == 'generateArticle') {
        $arrCallback[0] = 'RealURLBackend';
        break;
    }
}

$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['realurl_regeneratePageRoots'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['realurl_regeneratePageRoots'],
	'href'	=> 'key=realurl_regeneratePageRoots'
);
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['realurl_repair'] = array(
	'label'	=> &$GLOBALS['TL_LANG']['tl_page']['realurl_repair'],
	'href'	=> 'key=realurl_repair'
);

foreach($GLOBALS['TL_DCA']['tl_page']['palettes'] as $strSelector => &$strPalette) if($strSelector != '__selector__') {
    if($strSelector == 'root') {
        $strPalette = str_replace(',alias', ',alias,realurl_defaultInherit,realurl_childrenInherit', $strPalette);
    } else {
        $strPalette = str_replace(',alias', ',alias,realurl_inherit,realurl_childrenInherit', $strPalette);
    }
}

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']		= 'realurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave']	= true;

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_defaultInherit'] = array(
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['realurl_defaultInherit'],
    'inputType'     => 'checkbox',
    'eval' => array(
        'tl_class'          => 'w50 m12 cbx'
    )
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_inherit'] = array(
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['realurl_inherit'],
    'inputType'     => 'checkbox',
    'eval' => array(
        'tl_class'          => 'w50 m12 cbx'
    )
);

/*
$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_childrenInherit'] = array(
	'label'         => &$GLOBALS['TL_LANG']['tl_page']['realurl_childrenInherit'],
	'inputType'     => 'checkbox',
	'options_callback' => array('RealURLBackend', 'getChildrenInheritOptions'),
	'eval' => array(
		'multiple'	=> true,
		'tl_class'	=> 'clr'
	),
	'load_callback' => array(
		array('RealURLBackend', 'loadChildrenInherit')
	),
	'save_callback' => array(
		array('RealURLBackend', 'saveChildrenInherit')
	),
);
*/
