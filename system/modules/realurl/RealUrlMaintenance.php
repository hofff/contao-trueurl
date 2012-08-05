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
 * @copyright  MEN AT WORK 2011-2012
 * @author     MEN AT WORK <cms@men-at-work.de>
 * @license    GNU/LGPL
 */

include_once TL_ROOT . '/system/modules/backend/dca/tl_page.php';
include_once TL_ROOT . '/system/modules/realurl/dca/tl_page.php';

class RealUrlMaintenance extends Backend implements executable
{

    public function isActive()
    {
        return ($this->Input->get('act') == 'realurl');
    }

    public function run()
    {
        // If true rebuild all folder urls
        if ($this->Input->get('act') == 'realurl')
        {
            // Load helper class            
            $objPageRealUrl = new tl_page_realurl();

            // Get all rootpages
            $objRootPages = $this->Database
                    ->prepare("SELECT * FROM tl_page WHERE type='root'")
                    ->execute();

            if ($objRootPages->numRows != 0)
            {
                try
                {
                    while ($objRootPages->next())
                    {
                        // Load the current page
                        $objRoot = $objPageRealUrl->getPageDetails($objRootPages->id);

                        // Check if alias exist or create one
                        if ($objRoot->alias == '')
                        {
                            $strAlias = $objPageRealUrl->generateFolderAlias('', (object) array('id'           => $objRootPages->id, 'activeRecord' => $objRootPages), true);

                            $this->Database
                                    ->prepare("UPDATE tl_page SET alias=? WHERE id=?")
                                    ->execute($strAlias, $dc->id);
                        }

                        // Check if the subalias is enabled
                        if ($objRoot->subAlias)
                        {
                            $objPageRealUrl->generateAliasRecursive($objRootPages->id, true);
                        }
                    }
                }
                catch (Exception $exc)
                {
                    $objTemplate                  = new BackendTemplate('be_realurl_maintenance');
                    $objTemplate->isActive        = $this->isActive();
                    $objTemplate->realurlHeadline = $GLOBALS['TL_LANG']['tl_maintenance']['realurlHeadline'];
                    $objTemplate->realurlMessage  = $exc->getMessage();
                    $objTemplate->action          = "contao/main.php?do=maintenance";
                    $objTemplate->realurlLabel    = $GLOBALS['TL_LANG']['tl_maintenance']['realurlNote'];
                    $objTemplate->realurlSubmit   = $GLOBALS['TL_LANG']['tl_maintenance']['realurlSubmit'];
                    
                    return $objTemplate->parse();
                }
            }

            $this->redirect('contao/main.php?do=maintenance');
        }

        $objTemplate                  = new BackendTemplate('be_realurl_maintenance');
        $objTemplate->isActive        = $this->isActive();
        $objTemplate->realurlHeadline = $GLOBALS['TL_LANG']['tl_maintenance']['realurlHeadline'];
        $objTemplate->realurlMessage  = "";
        $objTemplate->action          = "contao/main.php?do=maintenance";
        $objTemplate->realurlLabel    = $GLOBALS['TL_LANG']['tl_maintenance']['realurlNote'];
        $objTemplate->realurlSubmit   = $GLOBALS['TL_LANG']['tl_maintenance']['realurlSubmit'];

        return $objTemplate->parse();
    }

}

?>
