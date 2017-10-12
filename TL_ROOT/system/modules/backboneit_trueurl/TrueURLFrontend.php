<?php

class TrueURLFrontend extends Frontend {

	public function hookGetPageIdFromUrl(array $arrFragments) {
		$arrFiltered = array_values(array_filter($arrFragments, array(__CLASS__, 'fragmentFilter')));

		if(!$arrFiltered) {
			return $arrFragments;
		}
		$arrFragments = $arrFiltered;

		$arrParams = $GLOBALS['BBIT']['TURL']['unrouteable'];
		$strUnrouteableWildcards = rtrim(str_repeat('?,', count($arrParams)), ',');

		do {
			$arrParams[] = implode('/', $arrFiltered);
			array_pop($arrFiltered);
		} while($arrFiltered);
		$strAliasWildcards = rtrim(str_repeat('?,', count($arrFragments)), ',');

		$arrParams[] = $this->Environment->host;

		if($GLOBALS['TL_CONFIG']['addLanguageToUrl']) {
			$strLangCond = 'AND (root.language = ? OR root.fallback = 1)';
			$arrParams[] = $this->Input->get('language');
			$strLangOrder = ', root.fallback = 1';
		}

		if(!BE_USER_LOGGED_IN) {
			$intTime = time();
			$strPagePublishCond = <<<EOT
AND (page1.start = '' OR page1.start < $intTime)
AND (page1.stop = '' OR page1.stop > $intTime)
AND (page1.published = 1)
EOT;
			$strRootPublishCond = <<<EOT
AND (root.start = '' OR root.start < $intTime)
AND (root.stop = '' OR root.stop > $intTime)
AND root.published = 1
EOT;
		}

		$strQuery = <<<EOT
SELECT	root.id AS root, page.*
FROM	tl_page AS root
LEFT JOIN (

	SELECT	page1.id,
			page1.alias,
			page1.bbit_turl_root,
			page1.bbit_turl_requestPattern,
			page1.bbit_turl_capturedParams,
			page1.bbit_turl_matchRequired
	FROM	tl_page AS page1
	WHERE	page1.type NOT IN ($strUnrouteableWildcards)
	AND		page1.alias IN ($strAliasWildcards)
	$strPagePublishCond

) AS page ON page.bbit_turl_root = root.id

WHERE	(root.type = 'root')
AND		(root.dns = '' OR root.dns = ?)
$strLangCond
$strRootPublishCond

ORDER BY page.id IS NULL, root.dns = ''$strLangOrder, LENGTH(page.alias) DESC, root.sorting
EOT;
		$objAlias = Database::getInstance()->prepare($strQuery)->limit(1)->execute($arrParams);

		if(!$objAlias->numRows) {
			$arrFragments = array(false);

			// this can not be handled by index.php since $arrFragments will be urldecoded,
			// which turns false into "", which is replaced with null, that is causing a
			// root page lookup
			$this->exit404($arrParams[0]);
			return $arrFragments;

		} elseif($objAlias->id) {
			array_splice($arrFragments, 0, substr_count($objAlias->alias, '/') + 1, $objAlias->alias);

		} else {
			$objHandler = new $GLOBALS['TL_PTY']['root']();
			$objAlias = $this->getPageDetails($objHandler->generate($objAlias->root, true));
			array_unshift($arrFragments, $objAlias->id);
		}

		$GLOBALS['BBIT']['TURL']['fragments'] = array_slice($arrFragments, 1);

		foreach(array_map('trim', explode(',', $objAlias->bbit_turl_capturedParams)) as $strParam) {
			$blnSkipEmpty = $strParam[0] == '?';
			$blnSkipEmpty && $strParam = substr($strParam, 1);
			$blnSkip = !strlen($strParam);
			$arrCaptured[] = array(urldecode($strParam), $blnSkip, $blnSkipEmpty);
			$blnSkip || $blnCaptures = true;
		}

		if($objAlias->bbit_turl_matchRequired || $blnCaptures) {
			$strRequest = implode('/', $GLOBALS['BBIT']['TURL']['fragments']);
			$strPattern = $objAlias->bbit_turl_requestPattern;
			strlen($strPattern) || $strPattern = '@^$@';

			if(preg_match($strPattern, $strRequest, $arrMatches)) {
				foreach($arrCaptured as $i => $arrParam) if(!$arrParam[1]) {
					$strValue = $arrMatches[$i + 1];
					if(!$arrParam[2] || strlen($strValue)) {
						$this->Input->setGet($arrParam[0], urldecode($strValue));
					}
				}
			} elseif($objAlias->bbit_turl_matchRequired) {
				$arrFragments[0] = false;
				$this->exit404($arrParams[0]);
			}

			if($objAlias->bbit_turl_matchRequired) {
				$arrFragments = array_slice($arrFragments, 0, 2 - (count($arrFragments) % 2));
			}
		}

		// Add the second fragment as auto_item if the number of fragments is even
		if($GLOBALS['TL_CONFIG']['useAutoItem'] && count($arrFragments) % 2 == 0) {
			array_splice($arrFragments, 1, 0, 'auto_item');
		}

		return $arrFragments;
	}

	protected function exit404($strRequest) {
		$this->import('FrontendUser', 'User');
		$this->User->authenticate();
		/** @var \Contao\PageError404 $objHandler */
		$objHandler = new $GLOBALS['TL_PTY']['error_404']();
		if (version_compare(VERSION, '4.0', '>=')) {
			throw new \Contao\CoreBundle\Exception\ResponseException($objHandler->getResponse());
		}
		$objHandler->generate($strRequest);
	}

	public static function fragmentFilter($strFragment) {
		return strlen($strFragment) && $strFragment != 'auto_item';
	}

}
