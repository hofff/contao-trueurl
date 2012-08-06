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
 * @copyright  Andreas Schempp 2008-2011
 * @copyright  MEN AT WORK 2011-2012
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @author     MEN AT WORK <cms@men-at-work.de>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] = preg_replace('@([,|;]allowedTags)([,|;])@', '$1,urlKeywords$2', $GLOBALS['TL_DCA']['tl_settings']['palettes']['default']);


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_settings']['fields']['urlKeywords'] = array
(
	'label'			=> &$GLOBALS['TL_LANG']['tl_settings']['urlKeywords'],
	'inputType'		=> 'text',
	'eval'			=> array('tl_class'=>'long'),
);

