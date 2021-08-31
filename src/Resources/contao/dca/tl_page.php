<?php

use Contao\System;
use Doctrine\DBAL\Types\Types;

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][]     = ['TrueURLBackend', 'onLoad'];
$GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl']      = $GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'];
$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'] = ['TrueURLBackend', 'labelPage'];

$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_alias']      = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow'],
    'route' => 'hofff_contao_true_url_alias',
];
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_regenerate'] = [
    'route' => 'hofff_contao_true_url_regenerate',
];
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_repair']     = [
    'route' => 'hofff_contao_true_url_repair',
];
$GLOBALS['TL_DCA']['tl_page']['list']['operations']['bbit_turl_autoInherit']       = [
    'icon'  => System::getContainer()->get('assets.packages')->getUrl('images/page_link.php', 'hofff_contao_true_url'),
    'route' => 'hofff_contao_true_url_auto_inherit',
];

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']       = 'trueurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave'] = true;
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'][]    = ['TrueURLBackend', 'resetFolderUrlConfig'];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_rootInheritProxy'] = [
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
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'w50 cbx m12',
    ],
    'sql' => [
        'type'    => 'string',
        'length'  => 1,
        'notnull' => false,
        'default' => '',
    ]
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_inherit'] = [
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'clr w50 cbx',
    ],
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 1,
        'notnull' => false,
        'default' => '',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_transparent'] = [
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'w50 cbx',
    ],
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 1,
        'notnull' => false,
        'default' => '',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_ignoreRoot'] = [
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'clr w50 cbx',
    ],
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 1,
        'notnull' => false,
        'default' => '',
    ]
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_fragment'] = [
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 128,
        'notnull' => false,
        'default' => '',
    ]
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_rootInherit'] = [
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 255,
        'notnull' => false,
        'default' => '',
    ]
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_root'] = [
    'sql' => [
        'type'    => Types::INTEGER,
        'length'  => 10,
        'notnull' => false,
        'default' => 0,
    ]
];
