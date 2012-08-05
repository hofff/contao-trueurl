<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
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
array_insert($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'], 0, array(array('tl_page_realurl', 'verifyAliases')));

foreach ($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'] as $i => $arrCallback)
{
    if ($arrCallback[1] == 'generateArticle')
    {
        $GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][$i][0] = 'tl_page_realurl';
        break;
    }
}

foreach ($GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'] as $i => $arrCallback)
{
    if ($arrCallback[1] == 'generateAlias')
    {
        $GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'][$i] = array('tl_page_realurl', 'generateFolderAlias');
        break;
    }
}

$GLOBALS['TL_DCA']['tl_page']['config']['onload_callback'][] = array('tl_page_realurl', 'checkSystem');

/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_page']['palettes']['root'] .= ';{folderurl_legend},folderAlias,subAlias,useRootAlias';
$GLOBALS['TL_DCA']['tl_page']['palettes']['__selector__'][] = 'realurl_overwrite';
$GLOBALS['TL_DCA']['tl_page']['subpalettes']['realurl_overwrite'] = 'realurl_basealias';

foreach ($GLOBALS['TL_DCA']['tl_page']['palettes'] as $keyPalette => $valuePalette)
{
    if ($keyPalette != "root" && $keyPalette != '__selector__')
    {
        $GLOBALS['TL_DCA']['tl_page']['palettes'][$keyPalette] = preg_replace('@([,|;]type)([,|;])@', '$1,realurl_overwrite$2', $GLOBALS['TL_DCA']['tl_page']['palettes'][$keyPalette]);
    }
}

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['rgxp'] = 'folderurl';
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['eval']['alwaysSave'] = true;
$GLOBALS['TL_DCA']['tl_page']['fields']['alias']['load_callback'][] = array('tl_page_realurl', 'hideParentAlias');

$GLOBALS['TL_DCA']['tl_page']['fields']['folderAlias'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_page']['folderAlias'],
    'inputType' => 'checkbox',
    'eval' => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['subAlias'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_page']['subAlias'],
    'inputType' => 'checkbox',
    'eval' => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['useRootAlias'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_page']['useRootAlias'],
    'inputType' => 'checkbox',
    'eval' => array('tl_class' => 'w50'),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_overwrite'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_page']['realurl_overwrite'],
    'inputType' => 'checkbox',
    'eval' => array(
        'submitOnChange' => true,
        'tl_class' => 'clr',
        'doNotCopy' => true
    ),
);

$GLOBALS['TL_DCA']['tl_page']['fields']['realurl_basealias'] = array(
    'label' => &$GLOBALS['TL_LANG']['tl_page']['alias'],
    'inputType' => 'text',
    'load_callback' => array(array('tl_page_realurl', 'loadFullAlias')),
    'eval' => array(
        'spaceToUnderscore' => true,
        'trailingSlash' => true,
        'doNotCopy' => true,
        'tl_class' => 'long'
    )
);

class tl_page_realurl extends tl_page
{

    /**
     * Only use the last portion of the page alias for the article alias
     * 
     * @param	DataContainer
     * @return	void
     * @link	http://www.contao.org/callbacks.html#onsubmit_callback
     * @version 1.0
     */
    public function generateArticle(DataContainer $dc)
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord)
        {
            return;
        }

        $arrAlias = explode('/', $dc->activeRecord->alias);
        $dc->activeRecord->alias = array_pop($arrAlias);

        parent::generateArticle($dc);
    }

    /**
     * Laod the current full alias
     * 
     * @param type $varValue
     * @param type $dc
     * @return type 
     */
    public function loadFullAlias($varValue, $dc)
    {
        // Load current page alias
        $objPage = $this->getPageDetails($dc->id);
        return $objPage->alias;
    }

    /**
     * Replaces the default contao core function to auto-generate a page alias if it has not been set yet.
     * 
     * @param	mixed
     * @param	DataContainer
     * @return	mixed
     * @link	http://www.contao.org/callbacks.html#save_callback
     * @version 2.0
     */
    public function generateFolderAlias($varValue, $dc, $useExtException = false)
    {
        // Load current page
        $objPage = $this->getPageDetails($dc->id);

        // Load root page
        if ($objPage->type == 'root')
        {
            $objRoot = $objPage;
        }
        else
        {
            $objRoot = $this->Database
                    ->prepare("SELECT * FROM tl_page WHERE id=?")
                    ->execute($objPage->rootId);
        }

        // Check if realurl is enabeld
        if (!$objRoot->folderAlias)
        {
            return parent::generateAlias($varValue, $dc);
        }

        if (in_array($varValue, $GLOBALS['URL_KEYWORDS']) && $useExtException == false)
        {
            throw new Exception($GLOBALS['TL_LANG']['ERR']['realUrlKeywords'], $objPage->id);
        }
        else if (in_array($varValue, $GLOBALS['URL_KEYWORDS']) && $useExtException == true)
        {
            $strUrl = $this->Environment->base . "contao/main.php?do=page&act=edit&id=" . $objPage->id;

            // The alias of the site includes a keyword. <a href="%s">%s (ID: $s)</a>
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['realUrlKeywordsExt'], $strUrl, $objPage->title, $objPage->id, $varValue), $objPage->id);
        }

        // Init vars
        $autoAlias = false;
        $blnRealUrlOverwrite = false;
        $strRealUrlOverwrite = "";

        if ($this->Input->post('realurl_overwrite') == true)
        {
            $blnRealUrlOverwrite = true;
            $strRealUrlOverwrite = $this->Input->post('realurl_basealias');
        }

        // Generate an alias if there is none
        if ($varValue == '')
        {
            $autoAlias = true;
            $varValue = standardize($objPage->title);
        }

        // Create Alias
        // Check if no overwrite, no rootpage and no add language to url
        if ($blnRealUrlOverwrite == false && $objPage->type != 'root' && $objRoot->useRootAlias == true)
        {
            $objParent = $this->Database->executeUncached("SELECT * FROM tl_page WHERE id=" . (int) $objPage->pid);
            $varValue = $objParent->alias . '/' . $varValue;
        }
        // Check if no overwrite, no rootpage and add language to url
        else if ($blnRealUrlOverwrite == false && $objPage->type != 'root' && $objRoot->useRootAlias == false)
        {
            $objParent = $this->Database->executeUncached("SELECT * FROM tl_page WHERE id=" . (int) $objPage->pid);

            // If parent is a root page don't use the alias from it
            if ($objParent->type == 'root')
            {
                $varValue = $varValue;
            }
            else
            {
                $varValue = $objParent->alias . '/' . $varValue;
            }
        }
        // If overwrite is enabled
        else if ($blnRealUrlOverwrite == true && $objPage->type != 'root')
        {
            if (strlen($strRealUrlOverwrite) == 0)
            {
                throw new Exception($GLOBALS['TL_LANG']['ERR']['emptyRealUrlOverwrite']);
            }
            else
            {
                $varValue = preg_replace("/\/$/", "", $strRealUrlOverwrite);
            }
        }
        // Check if rootpage
        else if ($objPage->type == 'root')
        {
            $varValue = $varValue;
        }

        // Check whether the page alias exists, if add laguage to url is enabled
        // Search only in one language page tree        
        if ($GLOBALS['TL_CONFIG']['addLanguageToUrl'] == true)
        {
            $objAlias = $this->Database
                    ->prepare("SELECT id FROM tl_page WHERE (id=? OR alias=?) AND id IN(" . implode(", ", $this->getChildRecords(array($objPage->rootId), 'tl_page', false)) . ")")
                    ->execute($dc->id, $varValue);
        }
        else
        {
            $objAlias = $this->Database
                    ->prepare("SELECT id FROM tl_page WHERE id=? OR alias=?")
                    ->execute($dc->id, $varValue);
        }

        if ($objAlias->numRows > ($autoAlias ? 0 : 1))
        {
            $arrPages = array();
            $strDomain = '';
            $strLanguage = '';

            while ($objAlias->next())
            {
                $objCurrentPage = $this->getPageDetails($objAlias->id);
                $domain = ($objCurrentPage->domain != '') ? $objCurrentPage->domain : '*';
                $language = (!$objCurrentPage->rootIsFallback) ? $objCurrentPage->rootLanguage : '*';

                // Store the current page data
                if ($objCurrentPage->id == $dc->id)
                {
                    $strDomain = $domain;
                    $strLanguage = $language;
                }
                else
                {
                    if ($GLOBALS['TL_CONFIG']['addLanguageToUrl'])
                    {
                        // Check domain and language
                        $arrPages[$domain][$language][] = $objAlias->id;
                    }
                    else
                    {
                        // Check the domain only
                        $arrPages[$domain][] = $objAlias->id;
                    }
                }
            }

            $arrCheck = $GLOBALS['TL_CONFIG']['addLanguageToUrl'] ? $arrPages[$strDomain][$strLanguage] : $arrPages[$strDomain];

            // Check if there are multiple results for the current domain
            if (!empty($arrCheck))
            {
                if ($autoAlias)
                {
                    $varValue .= '-' . $dc->id;
                }
                else
                {
                    throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
                }
            }
        }

        return $varValue;
    }

    /**
     * Hide the parent alias from the user when editing the alias field.
     * Including the root page alias.
     * 
     * @param	string
     * @param	DataContainer
     * @return	string
     * @link	http://www.contao.org/callbacks.html#load_callback
     * @version 2.0
     */
    public function hideParentAlias($varValue, $dc)
    {
        $objPage = $this->getPageDetails($dc->id);
        $objRoot = $this->Database->execute("SELECT * FROM tl_page WHERE id=" . (int) $objPage->rootId);

        if ($objRoot->folderAlias)
        {
            $arrFolders = trimsplit("/", $varValue);
            $varValue = array_pop($arrFolders);
        }

        return $varValue;
    }

    /**
     * Generate the page alias even if the alias field is hidden from the user
     * 
     * @param DataContainer
     * @return void
     * @link http://www.contao.org/callbacks.html#onsubmit_callback
     * @version 2.0
     */
    public function verifyAliases($dc)
    {
        // Check dc
        if (!$dc->activeRecord)
        {
            return;
        }

        // Load current page
        $objPage = $this->getPageDetails($dc->id);

        // Load root page
        if ($objPage->type == 'root')
        {
            $objRoot = $objPage;
        }
        else
        {
            $objRoot = $this->Database
                    ->prepare("SELECT * FROM tl_page WHERE id=?")
                    ->execute($objPage->rootId);
        }

        // Check if realurl is enabeld
        if (!$objRoot->folderAlias)
        {
            return;
        }

        // Check if alias exist or create one
        if ($dc->activeRecord->alias == '')
        {
            $strAlias = $this->generateFolderAlias('', $dc, false);

            $this->Database
                    ->prepare("UPDATE tl_page SET alias=? WHERE id=?")
                    ->execute($strAlias, $dc->id);
        }

        // Check if the subalias is enabled
        if ($objRoot->subAlias)
        {
            $this->generateAliasRecursive($dc->id);
        }
    }

    /**
     * 
     * @param type $intParentID
     */
    public function generateAliasRecursive($intParentID, $useExtException = false)
    {
        $arrChildren = $this->getChildRecords($intParentID, 'tl_page', true);

        if (count($arrChildren))
        {
            $objChildren = $this->Database
                    ->prepare("SELECT * FROM tl_page WHERE id IN (" . implode(',', $arrChildren) . ") ORDER BY id")
                    ->executeUncached();

            while ($objChildren->next())
            {
                // Check if overwrite is enabled
                if ($objChildren->realurl_overwrite == true)
                {
                    continue;
                }

                $arrFolders = trimsplit("/", $objChildren->alias);
                $strAlias = array_pop($arrFolders);
                $strAlias = $this->generateFolderAlias($strAlias, (object) array('id' => $objChildren->id, 'activeRecord' => $objChildren), $useExtException);

                $this->Database
                        ->prepare("UPDATE tl_page SET alias=? WHERE id=?")
                        ->executeUncached($strAlias, $objChildren->id);

                $this->generateAliasRecursive($objChildren->id, $useExtException);
            }
        }
    }

    public function checkSystem()
    {
        if ($GLOBALS['TL_CONFIG']['useAutoItem'] == true)
        {
            $_SESSION["TL_ERROR"][] = $GLOBALS['TL_LANG']['ERR']['autoItemEnabled'];
        }
    }

}