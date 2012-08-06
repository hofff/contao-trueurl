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
$GLOBALS['TL_LANG']['ERR']['realurl']                   = 'The keyword "%s" cannot be in your alias because an extension uses it.<br />Disallowed keywords: %s';
$GLOBALS['TL_LANG']['ERR']['aliasExistsFolder']         = 'The alias "%s" already exists! (the parent alias was automatically added)';
$GLOBALS['TL_LANG']['ERR']['noRootPageFound']           = 'There was no suitable website root found.';
$GLOBALS['TL_LANG']['ERR']['autoItemEnabled']           = 'The "auto_item parameters" setting can not be used with RealURL.';
$GLOBALS['TL_LANG']['ERR']['realUrlKeywords']           = 'The alias includes a reserved word for keywords.';
$GLOBALS['TL_LANG']['ERR']['realUrlKeywordsExt']        = 'The alias of the following page contains a reserved word for keywords. <a href="%s">%s (ID: %s)</a> Keyword: %s';
$GLOBALS['TL_LANG']['ERR']['emptyRealUrlOverwrite']     = 'The complete alias can not be empty.';