<?php

declare(strict_types=1);

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_autoInherit']        = [
    'Automatische Vererbung des Seitenalias prüfen und aktivieren',
    'Aktiviert die automatische Vererbung des Seitenalias für diese und alle Unterseiten, wenn der jeweilige Seitenalias mit der Elternseite beginnt.',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInherit']        = [
    'Vererbung des Startpunkt-Alias',
    'Gibt an, wie der Alias dieser Startpunktseite innerhalb der Alias-Vererbung verwendet wird.',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions'] = [
    'normal' => 'Normale Vererbung',
    'always' => 'Immer',
    'never'  => 'Niemals',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_defaultInherit']     = [
    'Standardmäßig die Alias-Vererbung aktivieren',
    'Die Standardeinstellungen für die Option "Alias von Elternseite erben" von neu erstellten Seiten innerhalb dieses Startpunkts.',
];

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit']     = [
    'Alias von Elternseite erben',
    'Der Alias der Elternseite wird diesem Seitenalias als Prefix vorangestellt. Änderungen im Elternalias werden automatisch auf diese Seite übertragen. Wenn die Elternseite die Option "Alias von der Vererbung ausschließen" aktiviert hat, dann wird der Alias der Großelternseite verwendet (und so weiter...).',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_transparent'] = [
    'Von Alias-Vererbung ausschließen',
    'Unterseiten, welche die Option "Alias von Elternseite erben" aktiviert haben, verwenden den Alias der Großelternseite, anstatt des Alias ihrer Elternseite (diese Seite).',
];
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_ignoreRoot']  = [
    'Alias des Startpunkts ignorieren',
    'Der Alias der Startpunktseite wird nicht automatisch hinzugefügt, unabhängig von den Einstellungen in der Startpunktseite.',
];

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_break'] = 'Alias nicht von Elternseite erben';

$GLOBALS['TL_LANG']['tl_page']['errNoAlias']         = 'Alias fehlt!';
$GLOBALS['TL_LANG']['tl_page']['errNoFragment']      = 'Alias-Fragment fehlt!';
$GLOBALS['TL_LANG']['tl_page']['errInvalidRoot']     = 'Alias beginnt nicht mit dem Alias des Startpunkts!';
$GLOBALS['TL_LANG']['tl_page']['errInvalidFragment'] = 'Alias endet nicht Alias-Fragment!';
