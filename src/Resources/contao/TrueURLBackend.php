<?php

use Contao\Backend;
use Contao\BackendUser;
use Contao\Config;
use Contao\Database;
use Contao\Input;
use Contao\PageModel;
use Contao\Session;
use Contao\System;

class TrueURLBackend
{
    private $folderUrlConfig;

	public function onLoad() {
		foreach($GLOBALS['TL_DCA']['tl_page']['palettes'] as $strSelector => &$strPalette) {
			if($strSelector === '__selector__') {
				continue;
			}

			if($strSelector === 'root' || $strSelector === 'rootfallback') {
				$strPalette = str_replace(',type', ',type,bbit_turl_rootInheritProxy,bbit_turl_defaultInherit', $strPalette);
			} else {
				$strPalette = str_replace(',type', ',type,bbit_turl_inherit,bbit_turl_transparent,bbit_turl_ignoreRoot', $strPalette);
			}
		}

		$arrConfig = &$GLOBALS['TL_DCA']['tl_page']['config'];
		foreach(array('oncreate', 'onsubmit', 'onrestore', 'oncopy', 'oncut') as $strCallback) {
			$strKey = $strCallback . '_callback';
			$arrConfig[$strKey] = (array) $arrConfig[$strKey];
			array_unshift($arrConfig[$strKey], array('TrueURLBackend', $strCallback . 'Page'));
		}

		foreach($arrConfig['onsubmit_callback'] as &$arrCallback) {
			if (! is_array($arrCallback)) {
				continue;
			}
			if ($arrCallback === ['tl_page', 'generateArticle']) {
				$arrCallback[0] = 'TrueURLBackend';
				break;
			}
			if ($arrCallback === ['Contao\CoreBundle\EventListener\DataContainer\ContentCompositionListener', 'generateArticleForPage']) {
				$arrCallback[0] = 'TrueURLBackend';
				$arrCallback[1] = 'generateArticle';
			}
		}

		array_unshift($GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'], array('TrueURLBackend', 'saveAlias'));
	}

	public function buttonAlias($strHREF, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot) {
		switch(Session::getInstance()->get('bbit_turl_alias')) {
			default:
				$strLabel = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow'][0];
				$strTitle = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow'][1];
				$intMode = 1;
				break;

			case 1:
				$strLabel = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasOnly'][0];
				$strTitle = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasOnly'][1];
				$intMode = 2;
				break;

			case 2:
				$strLabel = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasHide'][0];
				$strTitle = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasHide'][1];
				$intMode = 0;
				break;
		}
		return sprintf('%s<a href="%s" class="%s" title="%s"%s>%s</a> ',
			BackendUser::getInstance()->isAdmin ? '<br/><br/>' : ' &#160; :: &#160; ',
			Backend::addToUrl($strHREF . '&amp;bbit_turl_alias=' . $intMode),
			$strClass,
			specialchars($strTitle),
			$strAttributes,
			$strLabel
		);
	}

	public function buttonRegenerate($strHREF, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot) {
		return BackendUser::getInstance()->isAdmin ? sprintf(' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($strHREF),
			$strClass,
			specialchars($strTitle),
			$strAttributes,
			$strLabel
		) : '';
	}

	public function buttonRepair($strHREF, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot) {
		return BackendUser::getInstance()->isAdmin ? sprintf(' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
			Backend::addToUrl($strHREF),
			$strClass,
			specialchars($strTitle),
			$strAttributes,
			$strLabel
		) : '';
	}

	public function buttonAutoInherit($arrRow, $strHREF, $strLabel, $strTitle, $strIcon, $strAttributes, $strTable, $arrRootIDs, $arrChildRecordIDs, $blnCircularReference, $strPrevious, $strNext) {
		return BackendUser::getInstance()->isAdmin && Input::get('act') != 'paste' ? sprintf('<a href="%s" title="%s"%s>%s</a> ',
            Backend::addToUrl($strHREF . '&amp;id=' . $arrRow['id']),
			specialchars($strTitle),
			$strAttributes,
            Backend::generateImage($strIcon, $strLabel)
		) : '';
	}

	public function saveAlias($strAlias) {
		$this->folderUrlConfig = Config::get('folderUrl');
		Config::set('folderUrl', false);
		$GLOBALS['TL_HOOKS']['loadPageDetails'][self::class] = [self::class, 'setPageDetails'];

		return trim($strAlias, ' /');
	}

	public function resetFolderUrlConfig($strAlias) {
		Config::set('folderUrl', $this->folderUrlConfig);
		unset($GLOBALS['TL_HOOKS']['loadPageDetails'][self::class]);
		return $strAlias;
	}

	public function loadRootInherit($varValue, $objDC) {
		$varValue = $objDC->activeRecord->bbit_turl_rootInherit;
		return $varValue ? $varValue : 'normal';
	}

	protected $arrRootInherit = array();

	public function saveRootInherit($strNew, $objDC) {
		if($objDC->activeRecord) {
			$strOld = $objDC->activeRecord->bbit_turl_rootInherit;
			$strOld || $strOld = 'normal';
			if($strOld != $strNew) {
				$this->arrRootInherit[$objDC->id] = array($strOld, $strNew);
			}
		}
		return null;
	}

	protected function updateRootInherit($objDC) {
		if(!isset($this->arrRootInherit[$objDC->id])) {
			return;
		}

		if($objDC->activeRecord->type != 'root') {
			unset($this->arrRootInherit[$objDC->id]);
			return;
		}

		list($strOld, $strNew) = $this->arrRootInherit[$objDC->id];
		unset($this->arrRootInherit[$objDC->id]);

		$strQuery = <<<EOT
UPDATE	tl_page
SET		bbit_turl_rootInherit = ?
WHERE	id = ?
EOT;
		Database::getInstance()->prepare($strQuery)->execute($strNew, $objDC->id);

		$strAlias = $objDC->activeRecord->alias;
		if($strNew != 'always' || !strlen($strAlias)) {
			return;
		}

		// remove the root alias from fragments of all pages,
		// where the alias consists only of the fragment
		// and that do not ignore the root alias
		$strQuery = <<<EOT
UPDATE	tl_page
SET		bbit_turl_fragment = SUBSTRING(bbit_turl_fragment, ?)
WHERE	bbit_turl_root = ?
AND		bbit_turl_fragment LIKE ?
AND		bbit_turl_fragment = alias
AND		bbit_turl_ignoreRoot = ''
EOT;
        Database::getInstance()->prepare($strQuery)->execute(strlen($strAlias) + 2, $objDC->id, $strAlias . '/%');
	}

	public function oncreatePage($strTable, $intID, $arrSet, $objDC) {
		if(!$arrSet['pid']) {
			return;
		}

		$objParent = Backend::getPageDetails($arrSet['pid']);
		$intRootID = $objParent->type == 'root' ? $objParent->id : $objParent->rootId;

		if($intRootID) {
			$strQuery = <<<EOT
SELECT	bbit_turl_defaultInherit
FROM	tl_page
WHERE	id = ?
EOT;
			$blnDefaultInherit = Database::getInstance()->prepare($strQuery)->execute($intRootID)->bbit_turl_defaultInherit;
			$strQuery = <<<EOT
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

	public function onsubmitPage($objDC) {
		if(!$objDC->activeRecord) {
			return;
		}

		$strAlias = $objDC->activeRecord->alias;
		if(!strlen($strAlias)) {
			$this->saveAlias($strAlias);
			$tl_page = new tl_page();
			$strAlias = $tl_page->generateAlias('', $objDC);
			$this->resetFolderUrlConfig($strAlias);
		}

		$this->updateRootInherit($objDC);

		if (strlen($strAlias)) {
			$strFragment = $this->objTrueURL->extractFragment($objDC->id, $strAlias);
			$this->objTrueURL->update($objDC->id, $strFragment);
		}
	}

	public function oncopyPage($intID) {
		$this->objTrueURL->regeneratePageRoots($intID);
		$this->objTrueURL->update($intID);
	}

	public function oncutPage($objDC) {
		$this->objTrueURL->regeneratePageRoots($objDC->id);
		$this->objTrueURL->update($objDC->id);
	}

	public function onrestorePage($intID) {
		$this->objTrueURL->regeneratePageRoots($intID);
		$this->objTrueURL->update($intID);
	}

	public function generateArticle($objDC) {
		if(!$objDC->activeRecord) {
			return;
		}

		$strAlias = $objDC->activeRecord->alias;
		$arrAlias = explode('/', $strAlias);

		$objDC->activeRecord->alias = array_pop($arrAlias);
		$tl_page = new tl_page();
		$tl_page->generateArticle($objDC);
		$objDC->activeRecord->alias = $strAlias;
	}

	public static function setPageDetails(array $parents, PageModel $pageModel)
	{
		$pageModel->useFolderUrl = false;
	}

	protected $objTrueURL;

	public function __construct() {
		$this->objTrueURL = new TrueURL();
	}
}
