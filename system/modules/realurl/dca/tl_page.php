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
 * Replace core callbacks
 */
array_unshift($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'], array('RealURLBackend', 'verifyAliases'));

foreach ($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'] as &$arrCallback)
{
    if ($arrCallback[1] == 'generateArticle')
    {
        $arrCallback[0] = 'RealURLBackend';
        break;
    }
}

foreach ($GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'] as &$arrCallback)
{
    if ($arrCallback[1] == 'generateAlias')
    {
        $arrCallback = array('RealURLBackend', 'generateFolderAlias');
        break;
    }
}

/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] .= ';{realurl_legend},folderAlias,subAlias,useRootAlias';
$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][]       = 'realurl_overwrite';
$GLOBALS['TL_DCA']['tl_page']['subpalettes']['realurl_overwrite'] = 'realurl_basealias';

foreach ($GLOBALS['TL_DCA']['tl_page']['palettes'] as $strSelector => &$strPalette) if($strSelector != '__selector__')
{
    if ($strSelector != 'root')
    {
        $strPalette = str_replace(',alias', ',realurl_parentAlias,alias', $strPalette);
        $strPalette = str_replace(',type', ',type,realurl_overwrite', $strPalette);
    }
}

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp']       = 'folderurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave'] = true;
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['load_callback'][]    = array('tl_page_realurl', 'hideParentAlias');

$GLOBALS['TL_DCA']['tl_page']['fields']['folderAlias'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['folderAlias'],
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['subAlias'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['subAlias'],
    'inputType' => 'checkbox',
    'eval'      => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_rootAlias'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['realurl_rootAlias'],
    'inputType' => 'select',
	'options'	=> array('always', 'never'),
    'eval'      => array('includeBlankOption' => true, 'tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_overwrite'] = array(
    'label'     => &$GLOBALS['TL_LANG']['tl_page']['realurl_overwrite'],
    'inputType' => 'checkbox',
    'eval'      => array(
        'submitOnChange' => true,
        'tl_class'       => 'clr',
        'doNotCopy'      => true
    ),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_basealias'] = array(
	'label'         => &$GLOBALS['TL_LANG']['tl_page']['alias'],
	'inputType'     => 'text',
	'load_callback' => array(array('RealURLBackend', 'loadFullAlias')),
	'eval' => array(
		'spaceToUnderscore' => true,
		'trailingSlash'     => true,
		'doNotCopy'         => true,
		'tl_class'          => 'long'
	)
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_parentAlias'] = array(
    'label'         => &$GLOBALS['TL_LANG']['tl_page']['alias'],
    'inputType'     => 'text',
    'load_callback' => array(array('RealURLBackend', 'loadParentAlias')),
	'save_callback'	=> array(array('RealURLBackend', 'saveParentAlias')),
    'eval' => array(
        'spaceToUnderscore' => true,
        'trailingSlash'     => true,
        'doNotCopy'         => true,
        'tl_class'          => 'long'
    )
);
