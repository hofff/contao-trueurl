<?php

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow']
	= array('Seitenalias zeigen', 'Den Alias der Seiten in der Übersicht anzeigen.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasOnly']
	= array('Nur Seitenalias zeigen', 'Ausschließlich den Alias der Seiten in der Übersicht anzeigen.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasHide']
	= array('Seitenalias verstecken', 'Den Alias der Seiten in der Übersicht verstecken.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_regenerate']
	= array('Seiten regenerieren', 'Regeneriert alle direkten Referenzen auf die Startpunktseite in der sich die jeweilige Seite befindet. Diese Information wird von der Erweiterung "backboneit_trueurl" benötigt um eine effiziente Ermittelung der anzuzeigenden Seite durchzuführen.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_repair']
	= array('Seitenalias reparieren', 'Überprüft alle Seitenaliase und repariert diese, wenn nötig. Außerdem werden leere Aliase automatisch befüllt.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_autoInherit']
	= array('Automatische Vererbung des Seitenalias prüfen und aktivieren', 'Aktiviert die automatische Vererbung des Seitenalias für diese und alle Unterseiten, wenn der jeweilige Seitenalias mit der Elternseite beginnt.');
	
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInherit']
	= array('Vererbung des Startpunkt-Alias', 'Gibt an, wie der Alias dieser Startpunktseite innerhalb der Alias-Vererbung verwendet wird.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_rootInheritOptions'] = array(
	'normal'	=> 'Normale Vererbung',
	'always'	=> 'Immer',
	'never'		=> 'Niemals',
);
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_defaultInherit']
	= array('Standardmäßig die Alias-Vererbung aktivieren', 'Die Standardeinstellungen für die Option "Alias von Elternseite erben" von neu erstellten Seiten innerhalb dieses Startpunkts.');

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit']
	= array('Alias von Elternseite erben', 'Der Alias der Elternseite wird diesem Seitenalias als Prefix vorangestellt. Änderungen im Elternalias werden automatisch auf diese Seite übertragen. Wenn die Elternseite die Option "Alias von der Vererbung ausschließen" aktiviert hat, dann wird der Alias der Großelternseite verwendet (und so weiter...).');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_transparent']
	= array('Von Alias-Vererbung ausschließen', 'Unterseiten, welche die Option "Alias von Elternseite erben" aktiviert haben, verwenden den Alias der Großelternseite, anstatt des Alias ihrer Elternseite (diese Seite).');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_ignoreRoot']
	= array('Alias des Startpunkts ignorieren', 'Der Alias der Startpunktseite wird nicht automatisch hinzugefügt, unabhängig von den Einstellungen in der Startpunktseite.');

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_break']
	= 'Alias nicht von Elternseite erben';

$GLOBALS['TL_LANG']['tl_page']['errNoAlias']
	= 'Alias fehlt!';
$GLOBALS['TL_LANG']['tl_page']['errNoFragment']
	= 'Alias-Fragment fehlt!';
$GLOBALS['TL_LANG']['tl_page']['errInvalidRoot']
	= 'Alias beginnt nicht mit dem Alias des Startpunkts!';
$GLOBALS['TL_LANG']['tl_page']['errInvalidFragment']
	= 'Alias endet nicht Alias-Fragment!';
