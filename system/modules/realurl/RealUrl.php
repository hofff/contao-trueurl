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
class RealUrl extends Frontend
{

    /**
     * Parse url fragments to see if they are a parameter or part of the alias
     *
     * @param	array
     * @return	array
     * @link	http://www.contao.org/hooks.html?#getPageIdFromURL
     * @version 1.0
     */
    public function findAlias(array $arrFragments)
    {
        // Remove empty strings
        // Remove auto_item if found
        // Reset keys
        $arrFiltered = array_values(array_filter($arrFragments, array(__CLASS__, 'fragmentFilter')));
    
        if (!$arrFiltered)
        {
            return $arrFragments;
        }
        
        // Load the global alias list
        $objAlias = $this->Database
                ->prepare("SELECT * FROM tl_realurl_aliases WHERE alias IN('".implode("', '", $arrFragments)."')")
                ->execute();
        
        $arrKnownAliases = array();
        
        while ($objAlias->next())
        {
            $arrKnownAliases[] = $objAlias->alias;
        }

        // Build alias 
        // Append fragments until an url parameter is found or no fragments are left
        for ($i = 1; $arrFiltered[$i] !== null && !in_array($arrFiltered[$i], $GLOBALS['URL_KEYWORDS']) && in_array($arrFiltered[$i], $arrKnownAliases); $i++);
        array_splice($arrFiltered, 0, $i, implode('/', array_slice($arrFiltered, 0, $i)));

        return $arrFiltered;
    }

    public static function fragmentFilter($strFragment)
    {
        return strlen($strFragment) && $strFragment != 'auto_item';
    }

    /**
     * Validate a folderurl alias.
     * The validation is identical to the regular "alnum" except that it also allows for slashes (/).
     *
     * @param	string
     * @param	mixed
     * @param	Widget
     * @return	bool
     * @version 2.0
     */
    public function validateRegexp($strRegexp, $varValue, Widget $objWidget)
    {
        if ($strRegexp == 'folderurl')
        {
            if (stripos($varValue, "/") !== false || stripos($varValue, "\\") !== false)
            {
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alnum'], $objWidget->label));
            }

            if (!preg_match('/^[\pN\pL \.\/_-]*$/u', $varValue))
            {
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alnum'], $objWidget->label));
            }

            if (preg_match('#/' . implode('/|/', $GLOBALS['URL_KEYWORDS']) . '/|/' . implode('$|/', $GLOBALS['URL_KEYWORDS']) . '$#', $varValue, $match))
            {
                $strError = str_replace('/', '', $match[0]);
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['realurl'], $strError, implode(', ', $GLOBALS['URL_KEYWORDS'])));
            }

            return true;
        }

        return false;
    }

}