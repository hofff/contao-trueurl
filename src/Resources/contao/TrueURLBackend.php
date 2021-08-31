<?php

use Contao\Backend;
use Contao\Config;
use Contao\Database;
use Hofff\Contao\TrueUrl\EventListener\Hook\PageDetailsListener;

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
        unset($strPalette);

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
        unset($arrCallback);

		array_unshift($GLOBALS['TL_DCA']['tl_page']['fields']['alias']['save_callback'], array('TrueURLBackend', 'saveAlias'));
	}

	public function saveAlias($strAlias) {
		$this->folderUrlConfig = Config::get('folderUrl');
		Config::set('folderUrl', false);
		$GLOBALS['TL_HOOKS']['loadPageDetails'][PageDetailsListener::class] = [PageDetailsListener::class, '__invoke'];

		return trim($strAlias, ' /');
	}

	public function resetFolderUrlConfig($strAlias) {
		Config::set('folderUrl', $this->folderUrlConfig);
		unset($GLOBALS['TL_HOOKS']['loadPageDetails'][PageDetailsListener::class]);
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

		[$strOld, $strNew] = $this->arrRootInherit[$objDC->id];
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

	protected $objTrueURL;

	public function __construct() {
		$this->objTrueURL = new TrueURL();
	}
}
