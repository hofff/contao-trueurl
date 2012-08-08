<?php

class RealURLBackend extends tl_page {

    public function validateRegexp($strRegexp, $varValue, Widget $objWidget) {
        if($strRegexp == 'realurl') {
            if(!preg_match('/^[\pN\pL \.\/_-]*$/u', $varValue)) {
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alnum'], $objWidget->label));
            }

            return true;
        }

        return false;
    }
	

    
	public function regeneratePageRoots() {
		$objRoots = $this->Database->query(
			'SELECT id FROM tl_page WHERE type = \'root\''
		);
		
		while($objRoots->next()) {
			$arrDescendants = $this->getChildRecords($objRoots->id, 'tl_page');
			$arrDescendants[] = $objRoots->id;
			$this->Database->prepare(
				'UPDATE	tl_page SET realurl_root = ? WHERE id IN (' . implode(',', $arrDescendants) . ')'
			)->execute($objRoots->id);
		}
		
		// retrieve all pages not within a root page
		$arrIDs = array();
		$arrPIDs = array(0);
		while($arrPIDs) {
			$arrPIDs = $this->Database->query(
				'SELECT id FROM tl_page WHERE pid IN (' . implode(',', $arrPIDs) . ') AND type != \'root\''
			)->fetchEach('id');
			$arrIDs[] = $arrPIDs;
		}
		$arrIDs = call_user_func_array('array_merge', $arrIDs);
		
		$this->Database->query(
			'UPDATE	tl_page SET realurl_root = 0 WHERE id IN (' . implode(',', $arrIDs) . ')'
		);
	}
    
	public function oncreatePage($strTable, $intID, $arrSet, $objDC) {
		$objParent = $this->getPageDetails($arrSet['pid']);
		$intRootID = $objParent->type == 'root' ? $objParent->id : $objParent->rootId;
		$this->Database->prepare(
			'UPDATE tl_page SET realurl_inherit = (SELECT realurl_defaultInherit FROM tl_page WHERE id = ?) WHERE id = ?'
		)->execute($intRootID, $intID);
	}
	
	public function onsubmitPage($objDC) {
		if($objDC->activeRecord) {
			$strFragment = $objDC->activeRecord->alias;
			strlen($strFragment) || $this->generateAlias('', $objDC);
			if($objPage->type != 'root' && $objPage->realurl_inherit) {
				$strParentAlias = $this->getParentAlias($objDC->id);
				$strFragment = self::unprefix($strFragment, $strParentAlias);
			}
			$this->update($objDC->id, $strFragment);
		}
	}
	
	public function oncopyPage($intID) {
		$this->update($intID);
	}
	
	public function oncutPage($objDC) {
		$this->update($objDC->id);
	}
	
	public function onrestorePage($intID) {
		$this->update($intID);
	}
	
	public function repair() {
		$objPage = $this->Database->query(
			'SELECT id FROM tl_page WHERE type = \'root\' OR realurl_inherit = \'\''
		);
		while($objPage->next()) {
			$this->update($objPage->id);
		}
	}
	
	public function update($intPageID, $strFragment = null) {
		$objPage = $this->Database->prepare(
			'SELECT 	id, pid, alias, type, realurl_inherit, realurl_fragment
			FROM		tl_page
			WHERE		id = ?'
		)->executeUncached($intPageID);
		
		$strFragment !== null || $strFragment = $objPage->realurl_fragment;
		strlen($strFragment) || $strFragment = $this->makeAlias($intPageID);
		$strAlias = $strFragment;
		
		if($objPage->type != 'root' && $objPage->realurl_inherit) {
			$strParentAlias = $this->getParentAlias($objDC->id);
			strlen($strParentAlias) && $strAlias = $strParentAlias . '/' . $strFragment;
		}
		
		$this->storeAlias($intPageID, $strAlias, $strFragment);
		$this->updateDescendants($intPageID, $strAlias);
	}

	protected function updateDescendants($intPageID, $strParentAlias) {
		$objChildren = $this->Database->prepare(
			'SELECT	id, realurl_fragment
			FROM	tl_page
			WHERE	pid = ?
			AND		type != \'root\'
			AND		realurl_inherit = 1'
		)->execute($intPageID);
		
		while($objChildren->next()) {
			$strFragment = $objChildren->realurl_fragment;
			strlen($strFragment) || $strFragment = $this->makeAlias($objChildren->id);
			$strAlias = $strParentAlias . '/' . $strFragment;
			
			$this->storeAlias($objChildren->id, $strAlias, $strFragment);
			$this->updateDescendants($objChildren->id, $strAlias);
		}
	}
	
	protected function storeAlias($intPageID, $strAlias, $strFragment) {
		$this->Database->prepare(
			'UPDATE tl_page SET alias = ?, realurl_fragment = ? WHERE id = ?'
		)->executeUncached($strAlias, $strFragment, $intPageID);
	}
	
	protected function getParentAlias($intPageID) {
		return $this->Database->prepare(
			'SELECT	p2.alias
			FROM	tl_page AS p1
			JOIN	tl_page AS p2 ON p2.id = p1.pid
			WHERE	p1.id = ?'
		)->executeUncached($intPageID)->alias;
	}
	
	public static function unprefix($strAlias, $strPrefix) {
		return strlen($strPrefix) && self::isPrefix($strAlias, $strPrefix) ? substr($strAlias, strlen($strPrefix) + 1) : $strAlias;
	}

	public static function isPrefix($strAlias, $strPrefix) {
		return !strlen($strPrefix) || strncmp($strAlias, $strPrefix . '/', strlen($strPrefix) + 1) == 0;
	}

	public function generateArticle($objDC) {
		if(!$objDC->activeRecord) {
			return;
		}

		$strAlias = $objDC->activeRecord->alias;
		$arrAlias = explode('/', $strAlias);

		$objDC->activeRecord->alias = array_pop($arrAlias);
		parent::generateArticle($objDC);
		$objDC->activeRecord->alias = $strAlias;
	}
	
	public function makeAlias($intPageID) {
		$objPage = $this->getPageDetails($intPageID);
		
		if(!$objPage) {
			return 'alias-' . $intPageID;
		}
		
		$strAlias = standardize($objPage->title);
		
		$objAlias = $this->Database->prepare(
			'SELECT id FROM tl_page WHERE id != ? AND alias = ?'
		)->executeUncached($intPageID, $strAlias);

		if($objAlias->numRows) {
			$strAlias .= '-' . $intPageID;
		}

		return $strAlias;
	}
	
}
