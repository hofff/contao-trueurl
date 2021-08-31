<?php

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][]     = ['TrueURLBackend', 'onLoad'];
$GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl']      = $GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'];
$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'] = ['TrueURLBackend', 'labelPage'];

$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_alias']      = [
    'label'           => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow'],
    'href'            => 'key=bbit_turl_alias',
    'button_callback' => ['TrueURLBackend', 'buttonAlias'],
];
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_regenerate'] = [
    'label'           => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_regenerate'],
    'href'            => 'key=bbit_turl_regenerate',
    'button_callback' => ['TrueURLBackend', 'buttonRegenerate'],
];
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_repair']     = [
    'label'           => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_repair'],
    'href'            => 'key=bbit_turl_repair',
    'button_callback' => ['TrueURLBackend', 'buttonRepair'],
];
$GLOBALS['TL_DCA']['tl_page']['list']['operations']['bbit_turl_autoInherit']       = [
    'label'           => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_autoInherit'],
    'icon'            => \Contao\System::getContainer()->get('assets.packages')->getUrl('@HofffContaoTrueUrlBundle/images/page_link.php'),
    'href'            => 'key=bbit_turl_autoInherit',
    'button_callback' => ['TrueURLBackend', 'buttonAutoInherit'],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']       = 'trueurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave'] = true;
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'][]    = ['TrueURLBackend', 'resetFolderUrlConfig'];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_rootInheritProxy'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInherit'],
    'inputType'     => 'select',
    'options'       => ['normal', 'always', 'never'],
    'reference'     => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions'],
    'eval'          => [
        'doNotSaveEmpty' => true,
        'tl_class'       => 'clr w50',
    ],
    'load_callback' => [
        ['TrueURLBackend', 'loadRootInherit'],
    ],
    'save_callback' => [
        ['TrueURLBackend', 'saveRootInherit'],
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_defaultInherit'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_defaultInherit'],
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'w50 cbx m12',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_inherit'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit'],
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'clr w50 cbx',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_transparent'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_transparent'],
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'w50 cbx',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_ignoreRoot'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_ignoreRoot'],
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'clr w50 cbx',
    ],
];
