<?php

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = array('TrueURLBackend', 'onLoad');
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

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']		= 'trueurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave']	= true;
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
