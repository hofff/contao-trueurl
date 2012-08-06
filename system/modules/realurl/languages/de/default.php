<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  MEN AT WORK 2012
 * @package    Language
 * @license    GNU/LGPL 
 * @filesource
 */

/**
 * Error messages
 */
$GLOBALS['TL_LANG']['ERR']['realurl']                   = 'Das Wort "%s" kann nicht im Alias verwendet werden, weil es von einer Erweiterung reserviert ist.<br />Gesperrte Schlüsselwörter: %s';
$GLOBALS['TL_LANG']['ERR']['aliasExistsFolder']         = 'Der Alias "%s" existiert bereits! (Der übergeordnete Alias wurde automatisch hinzugefügt)';
$GLOBALS['TL_LANG']['ERR']['noRootPageFound']           = 'Es konnte kein passender Startpunkt gefunden werden.';
$GLOBALS['TL_LANG']['ERR']['autoItemEnabled']           = 'Die Einstellung "auto_item-Parameter" kann nicht mit RealURL verwendet werden.';
$GLOBALS['TL_LANG']['ERR']['realUrlKeywords']           = 'Der Alias beinhaltet ein für Keywords reserviertes Wort.';
$GLOBALS['TL_LANG']['ERR']['realUrlKeywordsExt']        = 'Der Alias der folgenden Seite beinhaltet ein für Keywords reserviertes Wort. <a href="%s">%s (ID: %s)</a> Keyword: %s';
$GLOBALS['TL_LANG']['ERR']['emptyRealUrlOverwrite']     = 'Der komplette Alias darf nicht leer sein.';