<?php

class TrueURL extends Controller {
	
	public function regeneratePageRoots($arrPageIDs = null, $blnOrphans = true) {
		if($arrPageIDs !== null) {
			$arrPageIDs = array_unique(array_map('intval', array_filter((array) $arrPageIDs, 'is_numeric')));
			$arrRoots = array();
			foreach($arrPageIDs as $intPageID) {
				$objPage = $this->getPageDetails($intPageID);
				$intRoot = $objPage->type == 'root' ? $objPage->id : intval($objPage->rootId);
				$arrRoots[$intRoot][] = $objPage->id;
			}
				
		} else {
			$arrRoots = $this->Database->query(
				'SELECT id FROM tl_page WHERE type = \'root\''
			)->fetchEach('id');
			$arrRoots = array_combine($arrRoots, $arrRoots);
		}
	
		foreach($arrRoots as $intRootID => $arrPageIDs) {
			$arrPageIDs = (array) $arrPageIDs;
				
			$arrDescendants = $this->getChildRecords($arrPageIDs, 'tl_page');
			$arrDescendants = array_merge($arrDescendants, $arrPageIDs);
				
			$this->Database->prepare(
				'UPDATE	tl_page SET bbit_turl_root = ? WHERE id IN (' . implode(',', $arrDescendants) . ')'
			)->execute($intRootID);
		}
	
		if(!$blnOrphans) {
			return;
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
			'UPDATE	tl_page SET bbit_turl_root = 0 WHERE id IN (' . implode(',', $arrIDs) . ')'
		);
	}
	
	public function repair() {
		$objPage = $this->Database->query(
			'SELECT id FROM tl_page WHERE type = \'root\' OR bbit_turl_inherit = \'\''
		);
		while($objPage->next()) {
			$this->update($objPage->id);
		}
	}
	
	/**
	 * Updates the alias and fragment of a given page and regenerates the alias
	 * of inheriting subpages.
	 * 
	 * If the page is not found, nothing is done.
	 * 
	 * Fragment calculation:
	 * 1. $strFragment given and non-empty?
	 * -yes-> Use this as new page fragment
	 * -no--> Go to 2.
	 * 
	 * 2. Is the existing page fragment non-empty?
	 * -yes-> Use this as new page fragment
	 * -no--> Go to 3.
	 * 
	 * 3. Is the existing page alias non-empty?
	 * -yes-> Go to 4.
	 * -no--> Go to 6.
	 * 
	 * 4. Is alias inheriting enabled for this page?
	 * -yes-> Unprefix the existing page alias, with the parent page alias and go to 5.
	 * -no--> Use the existing page alias as new page fragment
	 * 
	 * 5. Is the calculated fragment non-empty?
	 * -yes-> Use it as new page fragment
	 * -no--> Go to 6.
	 *  
	 * 6. Generate a fragment with makeAlias and use the result as new page fragment
	 * 
	 * @param integer $intPageID The page to be updated.
	 * @param string $strFragment The fragment to be used.
	 * @return boolean Whether the alias could be successfully updated.
	 */
	public function update($intPageID, $strFragment = null, $blnAutoInherit = false) {
		$objPage = $this->Database->prepare(
			'SELECT 	p1.id, p1.pid, p1.alias, p1.type, p1.bbit_turl_inherit, p1.bbit_turl_fragment,
						p2.alias AS parentAlias
			FROM		tl_page AS p1
			LEFT JOIN	tl_page AS p2 ON p2.id = p1.pid
			WHERE		p1.id = ?'
		)->executeUncached($intPageID);
		
		if(!$objPage->numRows) {
			return false;
		}
		
		$strAlias = $strFragment = $this->prepareFragment($objPage, $strFragment);
		$blnInherit = $objPage->bbit_turl_inherit;
		
		if($objPage->type != 'root' && strlen($objPage->parentAlias)) {
			if($blnAutoInherit && !$blnInherit) {
				$strUnprefixed = self::unprefix($strFragment, $objPage->parentAlias);
				$blnInherit = $strUnprefixed != $strFragment;
				$strFragment = $strUnprefixed;
			}
			if($blnInherit) {
				$strAlias = $objPage->parentAlias . '/' . $strFragment;
			}
		} 
		
		$this->storeAlias($intPageID, $strAlias, $strFragment, $blnInherit);
		
		if($strAlias == $objPage->alias && !$blnAutoInherit) {
			return true;
		}
		
		$objChildren = $this->Database->prepare(
			'SELECT	id, type, bbit_turl_inherit FROM tl_page WHERE pid = ?'
		)->executeUncached($intPageID);
		
		while($objChildren->next()) {
			if($blnAutoInherit) {
				$this->update($objChildren->id, null, true);
			} elseif($objChildren->bbit_turl_inherit && $objChildren->type != 'root') {
				$this->update($objChildren->id);
			}
		}
		
		return true;
	}
	
	protected function prepareFragment($objPage, $strFragment = null) {
		$strFragment = strval($strFragment);
		if(strlen($strFragment)) {
			return $strFragment;
		}
		
		$strFragment = $objPage->bbit_turl_fragment;
		if(strlen($strFragment)) {
			return $strFragment;
		}
		
		$strFragment = $objPage->alias;
		if($objPage->bbit_turl_inherit) {
			$strFragment = self::unprefix($strFragment, $objPage->parentAlias);
		}
		if(strlen($strFragment)) {
			return $strFragment;
		}
		
		return $this->makeAlias($objPage->id);
	}
	
	protected function storeAlias($intPageID, $strAlias, $strFragment, $blnInherit = null) {
		$arrSet = array('alias' => $strAlias, 'bbit_turl_fragment' => $strFragment);
		$blnInherit === null || $arrSet['bbit_turl_inherit'] = $blnInherit ? 1 : '';
		$this->Database->prepare(
			'UPDATE tl_page %s WHERE id = ?'
		)->set($arrSet)->executeUncached($intPageID);
	}
	
	public function getParentAlias($intPageID) {
		return $this->Database->prepare(
			'SELECT	p2.alias
			FROM	tl_page AS p1
			JOIN	tl_page AS p2 ON p2.id = p1.pid
			WHERE	p1.id = ?'
		)->executeUncached($intPageID)->alias;
	}
	
	public function makeAlias($intPageID) {
		$objPage = $this->getPageDetails($intPageID);
		
		if(!$objPage) {
			return 'page-' . $intPageID;
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
	
	public static function unprefix($strAlias, $strPrefix) {
		return strlen($strPrefix) && self::isPrefix($strAlias, $strPrefix) ? substr($strAlias, strlen($strPrefix) + 1) : $strAlias;
	}

	public static function isPrefix($strAlias, $strPrefix) {
		return !strlen($strPrefix) || strncmp($strAlias, $strPrefix . '/', strlen($strPrefix) + 1) == 0;
	}
	
	public function __construct() {
		parent::__construct();
		$this->import('Database');
	}
	
}
