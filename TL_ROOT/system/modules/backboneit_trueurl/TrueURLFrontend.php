<?php

class TrueURLFrontend extends Frontend {

	public function hookGetPageIdFromUrl(array $arrFragments) {
		$arrFiltered = array_values(array_filter($arrFragments, array(__CLASS__, 'fragmentFilter')));
		
		if(!$arrFiltered) {
			return $arrFragments;
		}
		
		$arrFragments = $arrFiltered;
		$arrParams = array();
		do {
			$arrParams[] = implode('/', $arrFiltered);
			array_pop($arrFiltered);
		} while($arrFiltered);
		$intFragments = count($arrParams);
		
		$arrParams[] = $this->Environment->host;
		
		if($GLOBALS['TL_CONFIG']['addLanguageToUrl']) {
			$strLangCond = 'AND (p2.language = ? OR p2.fallback = 1)';
			$arrParams[] = $this->Input->get('language');
			$strLangOrder = ', p2.fallback = 1';
		}
		
		if(!BE_USER_LOGGED_IN) {
			$intTime = time();
			$strPublishCond = '
			AND (p1.start = \'\' OR p1.start < ' . $intTime . ')
			AND (p1.stop = \'\' OR p1.stop > ' . $intTime . ')
			AND p1.published = 1
			AND (p2.start = \'\' OR p2.start < ' . $intTime . ')
			AND (p2.stop = \'\' OR p2.stop > ' . $intTime . ')
			AND p2.published = 1
			';
		}
		
		$objAlias = Database::getInstance()->prepare(
			'SELECT	p1.id, p1.alias
			FROM	tl_page AS p1
			JOIN	tl_page AS p2 ON p2.id = p1.bbit_turl_root
			WHERE	p1.alias IN (' . rtrim(str_repeat('?,', $intFragments), ',') . ')
			AND		(p2.dns = \'\' OR p2.dns = ?)
			AND		p1.type NOT IN (\'error_404\', \'error_403\')
			' . $strLangCond . '
			' . $strPublishCond . '
			ORDER BY p2.dns = \'\'' . $strLangOrder . ', LENGTH(p1.alias) DESC, p2.sorting'
		)->limit(1)->execute($arrParams);
		
		if($objAlias->numRows) {
			array_splice($arrFragments, 0, substr_count($objAlias->alias, '/') + 1, $objAlias->id);
			$GLOBALS['BBIT']['TURL']['fragments'] = array_slice($arrFragments, 1);
		} else {
			$arrFragments[0] = false;
			
			// this can not be handled by index.php since $arrFragments will be urldecoded,
			// which turns false into "", which is replaced with null, that is causing a
			// root page lookup
			$this->import('FrontendUser', 'User');
			$this->User->authenticate();
			$objHandler = new $GLOBALS['TL_PTY']['error_404']();
			$objHandler->generate($arrParams[0]);
		}
		
		// Add the second fragment as auto_item if the number of fragments is even
		if($GLOBALS['TL_CONFIG']['useAutoItem'] && count($arrFragments) % 2 == 0) {
			array_splice($arrFragments, 1, 0, 'auto_item');
		}
		
		return $arrFragments;
	}

	public static function fragmentFilter($strFragment) {
		return strlen($strFragment) && $strFragment != 'auto_item';
	}

}