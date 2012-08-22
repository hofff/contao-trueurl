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
	
		if($arrIDs) {
			$this->Database->query(
				'UPDATE	tl_page SET bbit_turl_root = 0 WHERE id IN (' . implode(',', $arrIDs) . ')'
			);
		}
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
	 * Extracts the alias fragment from given alias according to the alias
	 * inheritance settings that apply to the page with given ID.
	 * 
	 * If the alias inheritance settings are not available, e.g. page with given
	 * ID does not exists, the given alias is returned unmodified.
	 * 
	 * This function is meant to be used with alias from current user input and
	 * not to generate fragments from a stored alias.
	 * 
	 * @param integer $intPageID
	 * @param string $strAlias
	 * @throws InvalidArgumentException If $strAlias casts to an empty string
	 * @return string
	 */
	public function extractFragment($intPageID, $strAlias) {
		$strFragment = strval($strAlias);
		if(!strlen($strFragment)) {
			throw new InvalidArgumentException('Argument #2 must be a non-empty string');
		}
		
		$objPage = $this->Database->prepare(
			'SELECT 	id, pid, type, bbit_turl_inherit, bbit_turl_ignoreRoot
			FROM		tl_page
			WHERE		id = ?'
		)->executeUncached($intPageID);
		
		if(!$objPage->numRows) {
			return $strFragment;
		}
		
		$objRoot = $this->getRootPage($intPageID);
		
		if($objRoot && !$objPage->bbit_turl_ignoreRoot) {
			switch($objRoot->bbit_turl_rootInherit) {
				default:
				case 'normal':
					if($objPage->pid != $objRoot->id) {
						break;
					}
				case 'always':
					$strFragment = self::unprefix($strFragment, $objRoot->alias);
					break;
						
				case 'never':
					break;
			}
		}
		
		if($objPage->type != 'root' && $objPage->bbit_turl_inherit) {
			$strParentAlias = $this->getParentAlias($intPageID, $objRoot);
			$strFragment = self::unprefix($strFragment, $strParentAlias);
		}
		
		return $strFragment;
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
		$objRoot = $this->getRootPage($intPageID);
		$strParentAlias = $this->getParentAlias($intPageID, $objRoot);
		
		if($strFragment !== null) {
			$strFragment = strval($strFragment);
			if(!strlen($strFragment)) {
				return false;
			}
			$this->storeAlias($intPageID, $strFragment, $strFragment);
		}
		
		return $this->doUpdate($intPageID, $objRoot, $strParentAlias, $blnAutoInherit);
	}
	
	protected function doUpdate($intPageID, $objRoot, $strParentAlias, $blnAutoInherit) {
		$objPage = $this->Database->prepare(
			'SELECT 	id, pid, alias, type,
						bbit_turl_inherit, bbit_turl_fragment, bbit_turl_ignoreRoot,
						bbit_turl_rootInherit
			FROM		tl_page
			WHERE		id = ?'
		)->executeUncached($intPageID);
		
		if(!$objPage->numRows) {
			return false;
		}
		
		$strAlias = $strFragment = $this->prepareFragment($objPage, $objRoot, $strParentAlias);
		
		if($objPage->type == 'root') {
			// updating a root page:
			// - do not check inheriting
			// - set new root page for nested updates
			// - reset parent alias for nested updates
			$objRoot = $objPage;
			$strParentAlias = null;
		
		} else {
			// updating a normal page:
			$blnInherit = $objPage->bbit_turl_inherit;
			
			if(!$objPage->bbit_turl_ignoreRoot) {
				switch($objRoot->bbit_turl_rootInherit) {
					default:
					case 'normal':
						if($objPage->pid == $objRoot->id) {
							$strParentAlias = $objRoot->alias;
						}
						break;
						
					case 'always':
						$strRootAlias = $objRoot->alias;
						break;
						
					case 'never':
						break;
				}
			}
			
			if($blnAutoInherit && !$blnInherit) {
				$strUnprefixed = self::unprefix($strFragment, $strParentAlias);
				$blnInherit = $strUnprefixed != $strFragment;
				$strFragment = $strUnprefixed;
			}
			
			$strPrefix = $blnInherit ? trim($strRootAlias . '/' . $strParentAlias, '/') : $strRootAlias;
			$strAlias = trim($strPrefix . '/' . $strFragment, '/');
			
			if(!$objPage->bbit_turl_transparent) {
				$strParentAlias = $blnInherit ? trim($strParentAlias . '/' . $strFragment, '/') : $strFragment;
			}
		}
			
		$this->storeAlias($intPageID, $strAlias, $strFragment, $blnInherit);
		
		$blnUpdateAllChildren = $blnAutoInherit || $objRoot->bbit_turl_rootInherit == 'always';
		
		if(!$blnUpdateAllChildren && $strFragment == $objPage->bbit_turl_fragment && $strAlias == $objPage->alias) {
			return true;
		}
		
		$objChildren = $this->Database->prepare(
			'SELECT	id, type, bbit_turl_inherit FROM tl_page WHERE pid = ?'
		)->executeUncached($intPageID);
		
		while($objChildren->next()) {
			if($objChildren->type != 'root' && ($blnUpdateAllChildren || $objChildren->bbit_turl_inherit)) {
				$this->doUpdate($objChildren->id, $objRoot, $strParentAlias, $blnAutoInherit);
			}
		}
		
		return true;
	}
	
	protected function prepareFragment($objPage, $objRoot, $strParentAlias = null) {
		// use stored fragment
		$strFragment = $objPage->bbit_turl_fragment;
		if(strlen($strFragment)) {
			return $strFragment;
		}
		
		// create fragment from existing alias
		$strFragment = $objPage->alias;
		// remove parent alias, if inheriting is enabled
		if($objRoot && !$objPage->bbit_turl_ignoreRoot) {
			switch($objRoot->bbit_turl_rootInherit) {
				default:
				case 'normal':
					if($objPage->pid != $objRoot->id) {
						break;
					}
				case 'always':
					$strFragment = self::unprefix($strFragment, $objRoot->alias);
					break;
					
				case 'never':
					break;
			}
		}
		if($objPage->bbit_turl_inherit) {
			$strFragment = self::unprefix($strFragment, $strParentAlias);
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
	
	/**
	 * Get the alias of the nearest ancestor page of the given page id,
	 * that is not a root page and is not transparent in alias inheritance
	 * hierarchy.
	 * 
	 * @param integer $intPageID
	 * @return string
	 */
	public function getParentAlias($intPageID, $objRoot = null) {
		if(!$objRoot) {
			$objRoot = $this->getRootPage($intPageID);
		}
		do {
			$objParent = $this->Database->prepare(
				'SELECT	p2.id, p2.alias, p2.bbit_turl_transparent
				FROM	tl_page AS p1
				JOIN	tl_page AS p2 ON p2.id = p1.pid
				WHERE	p1.id = ?
				AND		p2.type != \'root\''
			)->executeUncached($intPageID);
			$intPageID = $objParent->id;
		} while($objParent->numRows && $objParent->bbit_turl_transparent);
		
		$strAlias = strval($objParent->alias);
		
		if($objRoot && !$objParent->bbit_turl_ignoreRoot) {
			switch($objRoot->bbit_turl_rootInherit) {
				default:
				case 'always':
					$strAlias = self::unprefix($strAlias, $objRoot->alias);
					break;
					
				case 'normal':
				case 'never':
					break;
			}
		}
		
		return $strAlias;
	}
	
	/**
	 * Get the root page of the given page id, if it has one.
	 * This function uses the direct root reference. If the target is not a root
	 * page or does not exists, the root page is retrieved via
	 * Controller::getPageDetails() and the root page references within that
	 * root page are repaired.
	 * 
	 * @param int $intPageID
	 * @return object
	 */
	public function getRootPage($intPageID, $blnCached = false) {
		$strMethod = $blnCached ? 'execute' : 'executeUncached';
		$objRoot = $this->Database->prepare(
			'SELECT	rt.id, rt.type, rt.alias, rt.bbit_turl_rootInherit
			FROM	tl_page AS p
			JOIN	tl_page AS rt ON rt.id = p.bbit_turl_root
			WHERE	p.id = ?'
		)->$strMethod($intPageID);
		
		if($objRoot->numRows && $objRoot->type == 'root') {
			return $objRoot;
		}
		
		$objPage = $this->getPageDetails($intPageID);
		if($objPage->type == 'root') {
			$intRootID = $objPage->id;
		} elseif($objPage->rootId) {
			$intRootID = $objPage->rootId;
		} else {
			return null;
		}
		
		$this->regeneratePageRoots($intRootID);
		$objRoot = $this->Database->prepare(
			'SELECT id, alias, bbit_turl_rootInherit FROM tl_page WHERE id = ?'
		)->executeUncached($intRootID);
		
		return $objRoot;
	}
	
	/**
	 * This is a simple alias generation function, which ALWAYS generates an
	 * alias for the given page id.
	 * 
	 * @param integer $intPageID
	 * @return string
	 */
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
	
	public function splitAlias($arrPage) {
		$strAlias = $arrPage['alias'];
		if(!strlen($strAlias)) {
			return null;
		}
		
		$arrAlias = array();
		if($arrPage['type'] == 'root') {
			switch($arrPage['bbit_turl_rootInherit']) {
				case 'always':
					$arrAlias['root'] = $strAlias;
					break;
					
				default:
				case 'normal':
				case 'never':
					$arrAlias['fragment'] = $strAlias;
					break;
			}
			return $arrAlias;
		}
		
		if(!$arrPage['bbit_turl_ignoreRoot']) {
			$objRoot = $this->getRootPage($arrPage['id'], true);
			
			if($objRoot) switch($objRoot->bbit_turl_rootInherit) {
				case 'always':
					$intLength = strlen($objRoot->alias);
					if($intLength && strncasecmp($strAlias, $objRoot->alias, $intLength) == 0) {
						$arrAlias['root'] = $objRoot->alias;
						$strAlias = substr($strAlias, $intLength + 1);
					} else {
						$arrAlias['err']['errInvalidRoot'] = true;
					}
					break;
					
				default:
				case 'normal':
				case 'never':
					break;
			}
		}
		
		if(!$arrPage['bbit_turl_inherit']) {
			$arrAlias['fragment'] = $strAlias;
			return $arrAlias;
		} 
		
		$intLength = strlen($arrPage['bbit_turl_fragment']);
		if(!$intLength) {
			$arrAlias['fragment'] = $strAlias;
			$arrAlias['err']['errNoFragment'] = true;
			return $arrAlias;
		}
		
		$strFragment = substr($strAlias, -$intLength);
		if($strFragment != $arrPage['bbit_turl_fragment']) {
			$arrAlias['fragment'] = $strAlias;
			$arrAlias['err']['errInvalidFragment'] = true;
			return $arrAlias;
		}
		
		$arrAlias['parent'] = trim(substr($strAlias, 0, -$intLength), '/');
		$arrAlias['fragment'] = $strFragment;
		return $arrAlias;
	}
	
	public static function unprefix($strAlias, $strPrefix) {
		return strlen($strPrefix) && self::isPrefix($strAlias, $strPrefix) ? substr($strAlias, strlen($strPrefix) + 1) : $strAlias;
	}

	public static function isPrefix($strAlias, $strPrefix) {
		$intLength = strlen($strPrefix);
		return !$intLength || strncmp($strAlias, $strPrefix . '/', $intLength + 1) == 0;
	}
	
	public function __construct() {
		parent::__construct();
		$this->import('Database');
	}
	
}
