<?php

class RealURLBackend extends tl_page {

	/**
	 * Only use the last portion of the page alias for the article alias
	 *
	 * @param	DataContainer
	 * @return	void
	 * @link	http://www.contao.org/callbacks.html#onsubmit_callback
	 * @version 1.0
	 */
	public function generateArticle(DataContainer $dc)
	{
		// Return if there is no active record (override all)
		if (!$dc->activeRecord)
		{
			return;
		}

		$strAlias = $dc->activeRecord->alias;
		$arrAlias = explode('/', $strAlias);

		$dc->activeRecord->alias = array_pop($arrAlias);
		parent::generateArticle($dc);
		$dc->activeRecord->alias = $strAlias;
	}

	/**
	 * Laod the current full alias
	 *
	 * @param type $varValue
	 * @param type $dc
	 * @return type
	 */
	public function loadFullAlias($varValue, $dc)
	{
		// Load current page alias
		$objPage = $this->getPageDetails($dc->id);
		return $objPage->alias;
	}

	/**
	 * Replaces the default contao core function to auto-generate a page alias if it has not been set yet.
	 *
	 * @param	mixed
	 * @param	DataContainer
	 * @param   boolean $useExtException If true an extended error message, with id, link and some more information, will be returned.
	 * @return	mixed
	 * @link	http://www.contao.org/callbacks.html#save_callback
	 * @version 2.0
	 */
	public function generateFolderAlias($varValue, $dc, $useExtException = false)
	{
		// Load current page
		$objPage = $this->getPageDetails($dc->id);

		// Load root page
		if ($objPage->type == 'root')
		{
			$objRoot = $objPage;
		}
		else
		{
			$objRoot = $this->Database
			->prepare("SELECT * FROM tl_page WHERE id=?")
			->execute($objPage->rootId);
		}

		// Check if realurl is enabled
		if (!$objRoot->folderAlias)
		{
			return parent::generateAlias($varValue, $dc);
		}

		if (in_array($varValue, $GLOBALS['URL_KEYWORDS']) && $useExtException == false)
		{
			throw new Exception($GLOBALS['TL_LANG']['ERR']['realUrlKeywords'], $objPage->id);
		}
		else if (in_array($varValue, $GLOBALS['URL_KEYWORDS']) && $useExtException == true)
		{
			$strUrl = $this->Environment->base . "contao/main.php?do=page&act=edit&id=" . $objPage->id;

			// The alias of the site includes a keyword. <a href="%s">%s (ID: $s)</a>
			throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['realUrlKeywordsExt'], $strUrl, $objPage->title, $objPage->id, $varValue), $objPage->id);
		}

		// Init vars
		$autoAlias           = false;
		$blnRealUrlOverwrite = false;
		$strRealUrlOverwrite = "";

		if ($this->Input->post('realurl_overwrite') == true)
		{
			$blnRealUrlOverwrite = true;
			$strRealUrlOverwrite = $this->Input->post('realurl_basealias');
		}

		// Generate an alias if there is none
		if ($varValue == '')
		{
			$autoAlias = true;
			$varValue  = standardize($objPage->title);
		}

		// Create alias
		// Check if no overwrite, no root page and no add language to url
		if ($blnRealUrlOverwrite == false && $objPage->type != 'root' && $objRoot->useRootAlias == true)
		{
			$objParent = $this->Database->executeUncached("SELECT * FROM tl_page WHERE id=" . (int) $objPage->pid);
			$varValue  = $objParent->alias . '/' . $varValue;
		}
		// Check if no overwrite, no root page and add language to url
		else if ($blnRealUrlOverwrite == false && $objPage->type != 'root' && $objRoot->useRootAlias == false)
		{
			$objParent = $this->Database->executeUncached("SELECT * FROM tl_page WHERE id=" . (int) $objPage->pid);

			// If parent is a root page don't use the alias from it
			if ($objParent->type == 'root')
			{
				$varValue = $varValue;
			}
			else
			{
				$varValue = $objParent->alias . '/' . $varValue;
			}
		}
		// If overwrite is enabled
		else if ($blnRealUrlOverwrite == true && $objPage->type != 'root')
		{
			if (strlen($strRealUrlOverwrite) == 0)
			{
				throw new Exception($GLOBALS['TL_LANG']['ERR']['emptyRealUrlOverwrite']);
			}
			else
			{
				$varValue = preg_replace("/\/$/", "", $strRealUrlOverwrite);
			}
		}
		// Check if rootpage
		else if ($objPage->type == 'root')
		{
			$varValue = $varValue;
		}

		// Check whether the page alias exists, if add language to url is enabled
		// Search only in one language page tree
		if ($GLOBALS['TL_CONFIG']['addLanguageToUrl'] == true)
		{
			$objAlias = $this->Database
			->prepare("SELECT id FROM tl_page WHERE (id=? OR alias=?) AND id IN(" . implode(", ", $this->getChildRecords(array($objPage->rootId), 'tl_page', false)) . ")")
			->execute($dc->id, $varValue);
		}
		else
		{
			$objAlias = $this->Database
			->prepare("SELECT id FROM tl_page WHERE id=? OR alias=?")
			->execute($dc->id, $varValue);
		}

		if ($objAlias->numRows > ($autoAlias ? 0 : 1))
		{
			$arrPages = array();
			$strDomain   = '';
			$strLanguage = '';

			while ($objAlias->next())
			{
				$objCurrentPage = $this->getPageDetails($objAlias->id);
				$domain         = ($objCurrentPage->domain != '') ? $objCurrentPage->domain : '*';
				$language       = (!$objCurrentPage->rootIsFallback) ? $objCurrentPage->rootLanguage : '*';

				// Store the current page data
				if ($objCurrentPage->id == $dc->id)
				{
					$strDomain   = $domain;
					$strLanguage = $language;
				}
				else
				{
					if ($GLOBALS['TL_CONFIG']['addLanguageToUrl'])
					{
						// Check domain and language
						$arrPages[$domain][$language][] = $objAlias->id;
					}
					else
					{
						// Check the domain only
						$arrPages[$domain][] = $objAlias->id;
					}
				}
			}

			$arrCheck = $GLOBALS['TL_CONFIG']['addLanguageToUrl'] ? $arrPages[$strDomain][$strLanguage] : $arrPages[$strDomain];

			// Check if there are multiple results for the current domain
			if (!empty($arrCheck))
			{
				if ($autoAlias)
				{
					$varValue .= '-' . $dc->id;
				}
				else
				{
					throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
				}
			}
		}

		return $varValue;
	}

	/**
	 * Hide the parent alias from the user when editing the alias field.
	 * Including the root page alias.
	 *
	 * @param	string
	 * @param	DataContainer
	 * @return	string
	 * @link	http://www.contao.org/callbacks.html#load_callback
	 * @version 2.0
	 */
	public function hideParentAlias($varValue, $dc)
	{
		$objPage = $this->getPageDetails($dc->id);
		$objRoot = $this->Database->execute("SELECT * FROM tl_page WHERE id=" . (int) $objPage->rootId);

		if ($objRoot->folderAlias)
		{
			$arrFolders = trimsplit("/", $varValue);
			$varValue   = array_pop($arrFolders);
		}

		return $varValue;
	}

	/**
	 * Generate the page alias even if the alias field is hidden from the user
	 *
	 * @param DataContainer
	 * @return void
	 * @link http://www.contao.org/callbacks.html#onsubmit_callback
	 * @version 2.0
	 */
	public function verifyAliases($dc)
	{
		// Check dc
		if (!$dc->activeRecord)
		{
			return;
		}

		// Load current page
		$objPage = $this->getPageDetails($dc->id);

		// Load root page
		if ($objPage->type == 'root')
		{
			$objRoot = $objPage;
		}
		else
		{
			$objRoot = $this->Database
			->prepare("SELECT * FROM tl_page WHERE id=?")
			->execute($objPage->rootId);
		}

		// Check if realurl is enabled
		if (!$objRoot->folderAlias)
		{
			return;
		}

		// Check if alias exists or create one
		if ($dc->activeRecord->alias == '')
		{
			$strAlias = $this->generateFolderAlias('', $dc, false);

			$this->Database
			->prepare("UPDATE tl_page SET alias=? WHERE id=?")
			->execute($strAlias, $dc->id);
		}

		// Check if the subalias is enabled
		if ($objRoot->subAlias)
		{
			$this->generateAliasRecursive($dc->id);
		}
	}

	/**
	 * Create all aliases for current page and subpages.
	 *
	 * @param int $intParentID ID of current page
	 * @param bool $useExtException See generateFolderAlias for more informations
	 * @return void
	 */
	public function generateAliasRecursive($intParentID, $useExtException = false)
	{
		$arrChildren = $this->getChildRecords($intParentID, 'tl_page', true);

		if (count($arrChildren))
		{
			$objChildren = $this->Database
			->prepare("SELECT * FROM tl_page WHERE id IN (" . implode(',', $arrChildren) . ") ORDER BY id")
			->executeUncached();

			while ($objChildren->next())
			{
				// Check if overwrite is enabled
				if ($objChildren->realurl_overwrite == true)
				{
					continue;
				}

				$arrFolders = trimsplit("/", $objChildren->alias);
				$strAlias   = array_pop($arrFolders);
				$strAlias   = $this->generateFolderAlias($strAlias, (object) array('id'           => $objChildren->id, 'activeRecord' => $objChildren), $useExtException);

				$this->Database
				->prepare("UPDATE tl_page SET alias=? WHERE id=?")
				->executeUncached($strAlias, $objChildren->id);

				$this->generateAliasRecursive($objChildren->id, $useExtException);
			}
		}
	}

}
