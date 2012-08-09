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
	
	public function update($intPageID, $strFragment = null) {
		$objPage = $this->Database->prepare(
			'SELECT 	id, pid, alias, type, bbit_turl_inherit, bbit_turl_fragment
			FROM		tl_page
			WHERE		id = ?'
		)->executeUncached($intPageID);
		
		$strParentAlias = $this->getParentAlias($intPageID);
		
		$strFragment === null
			&& $strFragment = $objPage->bbit_turl_fragment;
		strlen($strFragment)
			|| $strFragment = $objPage->bbit_turl_inherit ? self::unprefix($objPage->alias, $strParentAlias) : $objPage->alias;
		strlen($strFragment)
			|| $strFragment = $this->makeAlias($intPageID);
		$strAlias = $strFragment;
		$objPage->type != 'root'
			&& $objPage->bbit_turl_inherit
			&& strlen($strParentAlias)
			&& $strAlias = $strParentAlias . '/' . $strFragment;
		
		$this->storeAlias($intPageID, $strAlias, $strFragment);
		$this->updateDescendants($intPageID, $strAlias);
	}

	protected function updateDescendants($intPageID, $strParentAlias) {
		$objChildren = $this->Database->prepare(
			'SELECT	id, bbit_turl_fragment
			FROM	tl_page
			WHERE	pid = ?
			AND		type != \'root\'
			AND		bbit_turl_inherit = 1'
		)->execute($intPageID);
		
		while($objChildren->next()) {
			$strFragment = $objChildren->bbit_turl_fragment;
			strlen($strFragment) || $strFragment = $this->makeAlias($objChildren->id);
			$strAlias = $strParentAlias . '/' . $strFragment;
			
			$this->storeAlias($objChildren->id, $strAlias, $strFragment);
			$this->updateDescendants($objChildren->id, $strAlias);
		}
	}
	
	protected function storeAlias($intPageID, $strAlias, $strFragment) {
		$this->Database->prepare(
			'UPDATE tl_page SET alias = ?, bbit_turl_fragment = ? WHERE id = ?'
		)->executeUncached($strAlias, $strFragment, $intPageID);
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
