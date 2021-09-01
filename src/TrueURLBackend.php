<?php

namespace Hofff\Contao\TrueUrl;

use Contao\Backend;
use Contao\Database;
use Contao\System;
use tl_page;

class TrueURLBackend
{
    public function onLoad()
    {
        foreach ($GLOBALS['TL_DCA']['tl_page']['palettes'] as $strSelector => &$strPalette) {
            if ($strSelector === '__selector__') {
                continue;
            }

            if ($strSelector === 'root' || $strSelector === 'rootfallback') {
                $strPalette = str_replace(
                    ',type',
                    ',type,bbit_turl_rootInheritProxy,bbit_turl_defaultInherit',
                    $strPalette
                );
            } else {
                $strPalette = str_replace(
                    ',type',
                    ',type,bbit_turl_inherit,bbit_turl_transparent,bbit_turl_ignoreRoot',
                    $strPalette
                );
            }
        }
        unset($strPalette);

        $arrConfig = &$GLOBALS['TL_DCA']['tl_page']['config'];
        foreach (['oncreate', 'onsubmit'] as $strCallback) {
            $strKey             = $strCallback . '_callback';
            $arrConfig[$strKey] = (array) $arrConfig[$strKey];
            array_unshift($arrConfig[$strKey], [self::class, $strCallback . 'Page']);
        }

        foreach ($arrConfig['onsubmit_callback'] as &$arrCallback) {
            if (!is_array($arrCallback)) {
                continue;
            }
            if ($arrCallback === ['tl_page', 'generateArticle']) {
                $arrCallback[0] = self::class;
                break;
            }
            if ($arrCallback === [
                    'Contao\CoreBundle\EventListener\DataContainer\ContentCompositionListener',
                    'generateArticleForPage',
                ]) {
                $arrCallback[0] = self::class;
                $arrCallback[1] = 'generateArticle';
            }
        }
        unset($arrCallback);
    }

    public function oncreatePage($strTable, $intID, $arrSet, $objDC)
    {
        if (!$arrSet['pid']) {
            return;
        }

        $objParent = Backend::getPageDetails($arrSet['pid']);
        $intRootID = $objParent->type == 'root' ? $objParent->id : $objParent->rootId;

        if ($intRootID) {
            $strQuery          = <<<EOT
SELECT	bbit_turl_defaultInherit
FROM	tl_page
WHERE	id = ?
EOT;
            $blnDefaultInherit = Database::getInstance()->prepare($strQuery)->execute(
                $intRootID
            )->bbit_turl_defaultInherit;
            $strQuery          = <<<EOT
UPDATE	tl_page
SET		bbit_turl_root = ?,
		bbit_turl_inherit = ?
WHERE	id = ?
EOT;
            Database::getInstance()->prepare($strQuery)->execute($intRootID, $blnDefaultInherit, $intID);
        } else {
            $strQuery = <<<EOT
UPDATE	tl_page
SET		bbit_turl_root = 0
WHERE	id = ?
EOT;
            Database::getInstance()->prepare($strQuery)->execute($intID);
        }
    }

    public function onsubmitPage($objDC)
    {
        if (!$objDC->activeRecord) {
            return;
        }

        $strAlias = $objDC->activeRecord->alias;
        if (!strlen($strAlias)) {
            $tl_page  = new tl_page();
            $strAlias = $tl_page->generateAlias('', $objDC);
        }
        $strAlias = trim($strAlias, '/');

        if (strlen($strAlias)) {
            $strFragment = $this->objTrueURL->extractFragment($objDC->id, $strAlias);
            $this->objTrueURL->update($objDC->id, $strFragment);
        }
    }

    public function oncopyPage($intID)
    {
        $this->objTrueURL->regeneratePageRoots([$intID]);
        $this->objTrueURL->update($intID);
    }

    public function oncutPage($objDC)
    {
        $this->objTrueURL->regeneratePageRoots([$objDC->id]);
        $this->objTrueURL->update($objDC->id);
    }

    public function onrestorePage($intID)
    {
        $this->objTrueURL->regeneratePageRoots([$intID]);
        $this->objTrueURL->update($intID);
    }

    public function generateArticle($objDC)
    {
        if (!$objDC->activeRecord) {
            return;
        }

        $strAlias = $objDC->activeRecord->alias;
        $arrAlias = explode('/', $strAlias);

        $objDC->activeRecord->alias = array_pop($arrAlias);
        $tl_page                    = new tl_page();
        $tl_page->generateArticle($objDC);
        $objDC->activeRecord->alias = $strAlias;
    }

    protected TrueURL $objTrueURL;

    public function __construct()
    {
        $this->objTrueURL = System::getContainer()->get(TrueURL::class);
    }
}
