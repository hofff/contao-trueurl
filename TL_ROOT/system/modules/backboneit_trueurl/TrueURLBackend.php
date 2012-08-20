<?php

class TrueURLBackend extends Backend {

	public function hookLoadDataContainer($strTable) {
		if($strTable == 'tl_page') {
			$GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'] = $GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'];
			$GLOBALS['TL_DCA']['tl_page']['list']['label']['label_callback'] = array('TrueURLBackend', 'labelPage');
		}
	}

	public function buttonAlias($strHREF, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot) {
		if($this->Session->get('bbit_turl_alias')) {
			$strLabel = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasHide'][0];
			$strTitle = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasHide'][1];
			$blnState = 0;
		} else {
			$strLabel = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow'][0];
			$strTitle = $GLOBALS['TL_LANG']['tl_page']['bbit_turl_aliasShow'][1];
			$blnState = 1;
		}
		return sprintf('<br/><br/><a href="%s" class="%s" title="%s"%s>%s</a> ',
			$this->addToUrl($strHREF . '&amp;state=' . $blnState),
			$strClass,
			specialchars($strTitle),
			$strAttributes,
			$strLabel
		);
	}

	public function buttonRegenerate($strHREF, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot) {
		return $this->User->isAdmin ? sprintf(' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
			$this->addToUrl($strHREF),
			$strClass,
			specialchars($strTitle),
			$strAttributes,
			$strLabel
		) : '';
	}
	
	public function buttonRepair($strHREF, $strLabel, $strTitle, $strClass, $strAttributes, $strTable, $intRoot) {
		return $this->User->isAdmin ? sprintf(' &#160; :: &#160; <a href="%s" class="%s" title="%s"%s>%s</a> ',
			$this->addToUrl($strHREF),
			$strClass,
			specialchars($strTitle),
			$strAttributes,
			$strLabel
		) : '';
	}
	
	public function buttonAutoInherit($arrRow, $strHREF, $strLabel, $strTitle, $strIcon, $strAttributes, $strTable, $arrRootIDs, $arrChildRecordIDs, $blnCircularReference, $strPrevious, $strNext) {
		return $this->User->isAdmin ? sprintf('<a href="%s" title="%s"%s>%s</a> ',
			$this->addToUrl($strHREF . '&amp;id=' . $arrRow['id']),
			specialchars($strTitle),
			$strAttributes,
			$this->generateImage($strIcon, $strLabel)
		) : '';
	}
	
	private $blnRecurse = false;
	
	public function labelPage($row, $label, DataContainer $dc=null, $imageAttribute='', $blnReturnImage=false, $blnProtected=false) {
		$arrCallback = $this->blnRecurse
			? array('tl_page', 'addIcon')
			: $GLOBALS['TL_DCA']['tl_page']['list']['label']['bbit_turl'];
		
		$blnWasRecurse = $this->blnRecurse;
		$this->blnRecurse = true;
		
		$this->import($arrCallback[0]);
		$label = $this->$arrCallback[0]->$arrCallback[1]($row, $label, $dc, $imageAttribute, $blnReturnImage, $blnProtected);
		
		if($blnWasRecurse) {
			$this->blnRecurse = false;
			return $label;
		}
		
		if(!$this->Session->get('bbit_turl_alias')) {
			return $label;
		}
		
		if(!strlen($row['alias'])) {
			$label .= sprintf(' <span style="color:#CC5555;">[%s]</span>',
				$GLOBALS['TL_LANG']['tl_page']['errNoAlias']
			);
			
		} elseif(!$row['bbit_turl_inherit']) {
			$label .= sprintf(' <span style="color:#b3b3b3;">[<span style="color:#5C9AC9;">%s</span>]</span>',
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
				$strParentAlias = trim(substr($row['alias'], 0, -$intFragment), '/');
				if(strlen($strParentAlias)) {
					$label .= sprintf(' <span style="color:#b3b3b3;">[%s/<span style="color:#5C9AC9;">%s</span>]</span>',
						$strParentAlias,
						$strFragment
					);
					
				} else {
					$label .= sprintf(' <span style="color:#b3b3b3;">[<span style="color:#5C9AC9;">%s</span>]</span> <span style="color:#CC5555;">[%s]</span>',
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
	
	
    public function hookAddCustomRegexp($strRegexp, $varValue, Widget $objWidget) {
        if($strRegexp == 'trueurl') {
            if(!preg_match('/^[\pN\pL \.\/_-]*$/u', $varValue)) {
                $objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['alnum'], $objWidget->label));
            }
            return true;
        }
        return false;
    }
	
	public function keyAlias() {
		$this->Session->set('bbit_turl_alias', $this->Input->get('state') == 1);
		$this->redirect($this->getReferer());
	}
	
	public function keyRegenerate() {
		$this->objTrueURL->regeneratePageRoots();
		$this->redirect($this->getReferer());
	}
	
	public function keyRepair() {
		$this->objTrueURL->repair();
		$this->redirect($this->getReferer());
	}
	
	public function keyAutoInherit() {
		$this->objTrueURL->update($this->Input->get('id'), null, true);
		$this->redirect($this->getReferer());
	}
	
	public function saveAlias($strAlias) {
		return trim($strAlias, ' /');
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
			if(!strlen($strFragment)) {
				$tl_page = new tl_page();
				$tl_page->generateAlias('', $objDC);
			}
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
		$tl_page = new tl_page();
		$tl_page->generateArticle($objDC);
		$objDC->activeRecord->alias = $strAlias;
	}
	
	protected $objTrueURL;
	
	public function __construct() {
		parent::__construct();
		$this->import('BackendUser', 'User');
		$this->objTrueURL = new TrueURL();
	}
	
}
