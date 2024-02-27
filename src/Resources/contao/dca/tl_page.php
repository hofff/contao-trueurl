<?php

declare(strict_types=1);

use Contao\System;
use Doctrine\DBAL\Types\Types;

$GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'] =
    $GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'];

$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_regenerate'] = [
    'href'  => '',
    'class' => '',
];
$GLOBALS['TL_DCA']['tl_page']['list']['global_operations']['bbit_turl_repair']     = [
    'href'  => '',
    'class' => '',
];

/** @psalm-suppress PossiblyNullReference */
$GLOBALS['TL_DCA']['tl_page']['list']['operations']['bbit_turl_autoInherit'] = [
    'icon' => System::getContainer()
        ->get('assets.packages')
        ->getUrl('images/page_link.png', 'hofff_contao_true_url'),
];

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']       = 'trueurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave'] = true;

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_rootInheritProxy'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInherit'],
    'inputType'     => 'select',
    'options'       => ['normal', 'always', 'never'],
    'reference'     => &$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions'],
    'eval'          => [
        'doNotSaveEmpty' => true,
        'tl_class'       => 'clr w50',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_defaultInherit'] = [
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 cbx m12'],
    'default'   => '1',
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 1,
        'notnull' => false,
        'default' => '1',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_inherit'] = [
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr w50 cbx'],
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 1,
        'notnull' => true,
        'default' => '',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_transparent'] = [
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'w50 cbx'],
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 1,
        'notnull' => true,
        'default' => '',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_ignoreRoot'] = [
    'inputType' => 'checkbox',
    'eval'      => ['tl_class' => 'clr w50 cbx'],
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 1,
        'notnull' => true,
        'default' => '',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_fragment'] = [
    'sql' => [
        'type'    => Types::BINARY,
        'length'  => 255,
        'notnull' => true,
        'default' => '',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_rootInherit'] = [
    'sql' => [
        'type'    => Types::STRING,
        'length'  => 255,
        'notnull' => true,
        'default' => '',
    ],
];

$GLOBALS['TL_DCA']['tl_page']['fields']['bbit_turl_root'] = [
    'sql' => [
        'type'     => Types::INTEGER,
        'length'   => 10,
        'notnull'  => true,
        'unsigned' => true,
        'default'  => 0,
    ],
];
