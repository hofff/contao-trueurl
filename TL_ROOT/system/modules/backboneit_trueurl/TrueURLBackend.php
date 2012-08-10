<?php

class TrueURLBackend extends Backend {

	public function loadDataContainer($strTable) {
		if($strTable == 'tl_page') {
			$GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'] = $GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'];
			$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'] = array('TrueURLBackend', 'labelPage');
		}
	}
	
	public function labelPage($row, $label, DataContainer $dc=null, $imageAttribute='', $blnReturnImage=false, $blnProtected=false) {
		$arrCallback = $GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'];
		$this->import($arrCallback[0]);
		$label = $this->$arrCallback[0]->$arrCallback[1]($row, $label, $dc, $imageAttribute, $blnReturnImage, $blnProtected);
		
		if(!strlen($row['alias'])) {
			$label .= sprintf(' <span style="color:#CC5555;">[%s]</span>',
				$GLOBALS['TL_LANG']['tl_page']['errNoAlias']
			);
			
		} elseif(!$row['bbit_turl_inherit']) {
			$label .= sprintf(' <span style="color:#b3b3b3;">[%s]</span>',
				$row['alias']
			);
			
		} elseif(strlen($row['bbit_turl_fragment'])) {
			$intFragment = strlen($row['bbit_turl_fragment']);
			$strFragment = substr($row['alias'], -$intFragment);
			if($strFragment != $row['bbit_turl_fragment']) {
				$label .= sprintf(' <span style="color:#b3b3b3;">[%s]</span> <span style="color:#CC5555;">[%s]</span>',
					$row['alias'],
					$GLOBALS['TL_LANG']['tl_page']['errInvalidFragment']
				);
				
			} else {
				$strParentAlias = substr($row['alias'], 0, -$intFragment);
				if(!strlen($strParentAlias)) {
					$label .= sprintf(' <span style="color:#b3b3b3;">[%s<span style="color:#8AB858;">%s</span>]</span>',
						$strParentAlias,
						$strFragment
					);
					
				} else {
					$label .= sprintf(' <span style="color:#b3b3b3;">[<span style="color:#8AB858;">%s</span>]</span> <span style="color:#CC5555;">[%s]</span>',
						$strFragment,
						$GLOBALS['TL_LANG']['tl_page']['errInvalidParentAlias']
					);
				}
			}
			
		} else {
			$label .= sprintf(' <span style="color:#b3b3b3;">[%s]</span> <span style="color:#CC5555;">[%s]</span>',
				$row['alias'],
				$GLOBALS['TL_LANG']['tl_page']['errNoFragment']
			);
		}
		
		return $label;
	}
	
	
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
