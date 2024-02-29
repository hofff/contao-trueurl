<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_autoInherit']        = [
    'Check and enable automatic parent page alias inheritance',
    'Enables the automatic parent page alias inheritance for this page and all its descendants, if the page alias starts with the alias of the parent page.',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInherit']        = [
    'Root page alias inheritance',
    'Defines, how the alias of this root page is used within the alias inheritance hierarchy.',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions'] = [
    'normal' => 'Normal inheritance',
    'always' => 'Always',
    'never'  => 'Never',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_defaultInherit']     = [
    'Default setting for parent page alias inheritance',
    'The default setting for the option "Inherit parent page alias" of newly created page below this root page.',
];

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit']     = [
    'Inherit parent page alias',
    'The parent page alias will be used as a prefix for this page alias. Changes within the parent page alias are reflected within this alias. If the parent page has the option "Exclude from alias inheritance" activated, the alias of the grandparent page will be used (and so on...).',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_transparent'] = [
    'Exclude from alias inheritance',
    'Subpages, which have the option "Inherit parent page alias" enabled, will use the alias of the grandparent page instead of the alias from parent page (this page).',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_ignoreRoot']  = [
    'Ignore root page alias',
    'The root page alias is not automatically added as a prefix, no matter what settings are configured in the root page.',
];

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_break'] = 'Do not inherit parent page alias';

$GLOBALS['TL_LANG']['tl_page']['errNoAlias']         = 'Alias missing!';
$GLOBALS['TL_LANG']['tl_page']['errNoFragment']      = 'Alias fragment missing!';
$GLOBALS['TL_LANG']['tl_page']['errInvalidRoot']     = 'Alias does not start with the root page alias!';
$GLOBALS['TL_LANG']['tl_page']['errInvalidFragment'] = 'Alias does ends with the page alias fragment!';
