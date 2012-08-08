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
 * @author     Leo Unglaub <leo@leo-unglaub.net> 
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */
class RealURL extends Frontend {

    public function findAlias(array $arrFragments) {
        $arrFiltered = array_values(array_filter($arrFragments, array(__CLASS__, 'fragmentFilter')));
        
        if(!$arrFiltered) {
        	return $arrFragments;
        }
        
        $arrFragments = $arrFiltered;
        $arrParams = array();
        do $arrParams[] = implode('/', $arrFiltered); while(array_shift($arrFiltered));
        $intFragments = count($arrParams);
        
        $arrParams[] = $this->Environment->host;
        
        if($GLOBALS['TL_CONFIG']['addLanguageToUrl']) {
        	$strLangCond = 'AND (p2.language = ? OR p2.fallback = 1)';
        	$arrParams[] = $this->Input->get('language');
        	$strLangOrder = ', p2.fallback = 1';
        }
        
        if(!BE_USER_LOGGED_IN) {
        	$intTime = time();
        	$strPublishCond = '
        	AND (p1.start = \'\' OR p1.start < ' . $intTime . ')
        	AND (p1.stop = \'\' OR p1.stop > ' . $intTime . ')
        	AND p1.published = 1
        	AND (p2.start = \'\' OR p2.start < ' . $intTime . ')
        	AND (p2.stop = \'\' OR p2.stop > ' . $intTime . ')
        	AND p2.published = 1
        	';
        }
        
        $objAlias = Database::getInstance()->prepare(
        	'SELECT	p1.id, p1.alias
        	FROM	tl_page AS p1
        	JOIN	tl_page AS p2 ON p2.id = p1.realurl_root
        	WHERE	p1.alias IN (' . ltrim(str_repeat(',?', $intFragments), ',') . ')
        	AND		(p2.dns = \'\' OR p2.dns = ?)
        	AND		p1.type NOT IN (\'error_404\', \'error_403\')
        	' . $strLangCond . '
        	' . $strPublishCond . '
        	ORDER BY p2.dns = \'\'' . $strLangOrder . ', LENGTH(p1.alias) DESC, p2.sorting'
        )->limit(1)->execute($arrParams);
        
        if($objAlias->numRows) {
        	array_splice($arrFragments, 0, substr_count($objAlias->alias, '/') + 1, $objAlias->id);
        } else {
        	$arrFragments[0] = false;
        }
        
        // Add the second fragment as auto_item if the number of fragments is even
        if($GLOBALS['TL_CONFIG']['useAutoItem'] && count($arrFragments) % 2 == 0) {
        	array_splice($arrFragments, 1, 0, 'auto_item');
        }
        
        return $arrFragments;
    }

    public static function fragmentFilter($strFragment) {
        return strlen($strFragment) && $strFragment != 'auto_item';
    }

}