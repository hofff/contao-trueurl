<?php

class TrueURLBackend extends Backend {

    public function addCustomRegexp($strRegexp, $varValue, Widget $objWidget) {
        if($strRegexp == 'trueurl') {
            if(!preg_match('/^[\pN\pL \.\/_-]*$/u', $varValue)) {
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alnum'], $objWidget->label));
            }
            return true;
        }
        return false;
    }
	
	public function keyRegenerate() {
		$this->objTrueURL->regeneratePageRoots();
		$this->redirect($this->getReferer());
	}
	
	public function keyRepair() {
		$this->objTrueURL->repair();
		$this->redirect($this->getReferer());
	}
    
	public function oncreatePage($strTable, $intID, $arrSet, $objDC) {
		$objParent = $this->getPageDetails($arrSet['pid']);
		$intRootID = $objParent->type == 'root' ? $objParent->id : $objParent->rootId;
		$this->Database->prepare(
			'UPDATE tl_page SET bbit_turl_root = ?, bbit_turl_inherit = (SELECT bbit_turl_defaultInherit FROM tl_page WHERE id = ?) WHERE id = ?'
		)->execute($intRootID, $intRootID, $intID);
	}
	
	public function onsubmitPage($objDC) {
		if($objDC->activeRecord) {
			$strFragment = $objDC->activeRecord->alias;
			strlen($strFragment) || $this->tl_page->generateAlias('', $objDC);
			if($objDC->activeRecord->type != 'root' && $objDC->activeRecord->bbit_turl_inherit) {
				$strParentAlias = $this->objTrueURL->getParentAlias($objDC->id);
				$strFragment = TrueURL::unprefix($strFragment, $strParentAlias);
			}
			$this->objTrueURL->update($objDC->id, $strFragment);
		}
	}
	
	public function oncopyPage($intID) {
		$this->objTrueURL->update($intID);
		$this->objTrueURL->regeneratePageRoots($intID);
	}
	
	public function oncutPage($objDC) {
		$this->objTrueURL->update($objDC->id);
		$this->objTrueURL->regeneratePageRoots($objDC->id);
	}
	
	public function onrestorePage($intID) {
		$this->objTrueURL->update($intID);
		$this->objTrueURL->regeneratePageRoots($intID);
	}

	public function generateArticle($objDC) {
		if(!$objDC->activeRecord) {
			return;
		}

		$strAlias = $objDC->activeRecord->alias;
		$arrAlias = explode('/', $strAlias);

		$objDC->activeRecord->alias = array_pop($arrAlias);
		$this->tl_page->generateArticle($objDC);
		$objDC->activeRecord->alias = $strAlias;
	}
	
	protected $tl_page;
	
	protected $objTrueURL;
	
	public function __construct() {
		parent::__construct();
		$this->tl_page = new tl_page();
		$this->objTrueURL = new TrueURL();
	}
	
}
