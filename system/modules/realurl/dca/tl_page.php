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

array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'], array('RealURLBackend', 'onsubmitPage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onrestore_callback'], array('RealURLBackend', 'onrestorePage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncopy_callback'], array('RealURLBackend', 'oncopyPage'));
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['oncut_callback'], array('RealURLBackend', 'oncutPage'));

foreach($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'] as &$arrCallback) {
    if($arrCallback[1] == 'generateArticle') {
        $arrCallback[0] = 'RealURLBackend';
        break;
    }
}

foreach($GLOBALS['TL_DCA']['tl_page']['palettes'] as $strSelector => &$strPalette) if($strSelector != '__selector__') {
    if($strSelector == 'root') {
        $strPalette = str_replace(',alias', ',alias,realurl_defaultInherit,realurl_childrenInherit', $strPalette);
    } else {
        $strPalette = str_replace(',alias', ',alias,realurl_inherit,realurl_childrenInherit', $strPalette);
    }
}

$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']		= 'realurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave']	= true;

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_defaultInherit'] = array(
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['realurl_defaultInherit'],
    'inputType'     => 'checkbox',
    'eval' => array(
        'tl_class'          => 'w50 m12 cbx'
    )
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_inherit'] = array(
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['realurl_inherit'],
    'inputType'     => 'checkbox',
    'eval' => array(
        'tl_class'          => 'w50 m12 cbx'
    )
);

/*
$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_childrenInherit'] = array(
	'label'         => &$GLOBALS['TL_LANG']['tl_page']['realurl_childrenInherit'],
	'inputType'     => 'checkbox',
	'options_callback' => array('RealURLBackend', 'getChildrenInheritOptions'),
	'eval' => array(
		'multiple'	=> true,
		'tl_class'	=> 'clr'
	),
	'load_callback' => array(
		array('RealURLBackend', 'loadChildrenInherit')
	),
	'save_callback' => array(
		array('RealURLBackend', 'saveChildrenInherit')
	),
);
*/