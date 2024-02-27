<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Types;
use Hofff\Contao\TrueUrl\DataContainer\Page\LabelViewMode;

$GLOBALS['TL_DCA']['tl_user']['fields']['hofff_trueurl_view_mode'] = [
    'inputType' => 'select',
    'exclude'   => true,
    'default'   => LabelViewMode::TITLE_AND_ALIAS->value,
    'eval'      => ['tl_class' => 'clr w50'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user']['hofff_trueurl_view_modes'],
    'sql'       => [
        'type'    => Types::STRING,
        'length'  => 16,
        'notnull' => true,
        'default' => LabelViewMode::TITLE_AND_ALIAS->value,
    ],
];
