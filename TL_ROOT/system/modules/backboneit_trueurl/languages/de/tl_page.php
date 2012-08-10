<?php

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_regenerate']
	= array('Seiten regenerieren', 'Regeneriert alle direkten Referenzen auf die Startpunktseite in der sich die jeweilige Seite befindet. Diese Information wird von der Erweiterung "backboneit_trueurl" benötigt um eine effiziente Ermittelung der anzuzeigenden Seite durchzuführen.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_repair']
	= array('Seitenalias reparieren', 'Überprüft und repariert gegebenenfalls alle Seitenaliase, welche die Option "Alias der Elternseite als Prefix verwenden" benutzen. Außerdem werden leere Aliase automatisch befüllt.');

$GLOBALS['TL_LANG']['tl_page']['bbit_turl_defaultInherit']
	= array('Standardmäßig den Alias der Elternseite als Prefix verwenden', 'Die Standardeinstellungen für die Option "Alias der Elternseite als Prefix verwenden" von neu erstellten Seiten unterhalb dieses Startpunkts.');
$GLOBALS['TL_LANG']['tl_page']['bbit_turl_inherit']
	= array('Alias der Elternseite als Prefix verwenden', 'Der Alias der Elternseite wird als diesem Seitenalias als Prefix vorangestellt. Änderungen im Elternalias werden automatisch auf diese Seite übertragen.');

$GLOBALS['TL_LANG']['tl_page']['errNoAlias']
	= 'Alias fehlt!';
$GLOBALS['TL_LANG']['tl_page']['errNoFragment']
	= 'Alias-Fragment fehlt! Reperatur durchführen.';
$GLOBALS['TL_LANG']['tl_page']['errInvalidFragment']
	= 'Alias endet nicht Alias-Fragment!';
$GLOBALS['TL_LANG']['tl_page']['errInvalidParentAlias']
	= 'Alias der Elternseite fehlt!';
