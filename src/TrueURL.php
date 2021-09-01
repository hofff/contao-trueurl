<?php

namespace Hofff\Contao\TrueUrl;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use function count;

final class TrueURL
{
    private Connection $connection;

    private ContaoFramework $framework;

    public function __construct(Connection $connection, ContaoFramework $framework)
    {
        $this->connection = $connection;
        $this->framework  = $framework;
    }

    public function regeneratePageRoots(?array $pageIds = null, $orphans = true): void
    {
        $this->framework->initialize();
        $database = $this->framework->createInstance(Database::class);

        if ($pageIds !== null) {
            $pageIds = array_unique(array_map('intval', array_filter($pageIds, 'is_numeric')));
            $rootIds = [];
            foreach ($pageIds as $pageId) {
                $pageModel = PageModel::findWithDetails($pageId);
                if (!$pageModel) {
                    continue;
                }

                $intRoot             = $pageModel->type === 'root' ? (int) $pageModel->id : (int) $pageModel->rootId;
                $rootIds[$intRoot][] = (int) $pageModel->id;
            }
        } else {
            $query = <<<'EOT'
SELECT	id
FROM	tl_page
WHERE	type = 'root'
EOT;
            $rootIds = $this->connection->executeQuery($query)->fetchFirstColumn();
            $rootIds = array_combine($rootIds, $rootIds);
        }

        foreach ($rootIds as $rootId => $pageIds) {
            $pageIds     = (array) $pageIds;
            $descendants = $database->getChildRecords($pageIds, 'tl_page');
            $descendants = array_merge($descendants, $pageIds);
            $query       = <<<'EOT'
UPDATE	tl_page
SET		bbit_turl_root = :rootId
WHERE	id IN (:descendants)
EOT;

            $this->connection->executeQuery(
                $query,
                ['rootId' => $rootId, 'descendants' => $descendants],
                ['descendants' => Connection::PARAM_STR_ARRAY]
            );
        }

        if (!$orphans) {
            return;
        }

        // retrieve all pages not within a root page
        $arrIDs  = [];
        $arrPIDs = [0];
        while ($arrPIDs) {
            $query = <<<'EOT'
SELECT	id
FROM	tl_page
WHERE	pid IN (:pids)
AND		type != 'root'
EOT;

            $arrPIDs = $this->connection
                ->executeQuery($query, ['pids' => $arrPIDs], ['pids' => Connection::PARAM_STR_ARRAY])
                ->fetchFirstColumn();

            $arrIDs[] = $arrPIDs;
        }
        $arrIDs = array_merge(...$arrIDs);

        if ($arrIDs) {
            $query = <<<'EOT'
UPDATE	tl_page
SET		bbit_turl_root = 0
WHERE	id IN (:ids)
EOT;

            $this->connection->executeQuery($query, ['ids' => $arrIDs], ['ids' => Connection::PARAM_STR_ARRAY]);
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
     * @param string  $strAlias
     *
     * @return string
     * @throws InvalidArgumentException If $strAlias casts to an empty string
     */
    public function extractFragment($intPageID, $strAlias)
    {
        $strFragment = strval($strAlias);
        if (!strlen($strFragment)) {
            throw new InvalidArgumentException('Argument #2 must be a non-empty string');
        }

        $strQuery   = <<<'EOT'
SELECT 	id, pid, type, bbit_turl_inherit, bbit_turl_ignoreRoot
FROM	tl_page
WHERE	id = ?
EOT;
        $statement  = $this->connection->executeQuery($strQuery, [$intPageID]);
        $pageResult = $statement->fetchAssociative();

        if (!$statement->rowCount() || $pageResult['type'] === 'root') {
            return $strFragment;
        }

        $objRoot = $this->getRootPage($intPageID);

        if ($objRoot && !$pageResult['bbit_turl_ignoreRoot']) {
            switch ($objRoot->bbit_turl_rootInherit) {
                default:
                case 'normal':
                    $pageResult['pid'] == $objRoot->id && $strParentAlias = $objRoot->alias;
                    break;

                case 'always':
                    $strFragment = self::unprefix($strFragment, $objRoot->alias);
                    break;

                case 'never':
                    break;
            }
        }

        if ($pageResult['bbit_turl_inherit']) {
            $strParentAlias || $strParentAlias = $this->getParentAlias($intPageID, $objRoot);
            $strFragment = self::unprefix($strFragment, $strParentAlias);
        }

        return $strFragment;
    }

    public function repair(): array
    {
        $query = <<<'EOT'
SELECT	id
FROM	tl_page
WHERE	type = 'root'
EOT;
        $statement = $this->connection->executeQuery($query);
        $success   = [];

        while ($row = $statement->fetchAssociative()) {
            $objRoot        = $this->getRootPage($row['id']);
            $strParentAlias = $this->getParentAlias($row['id'], $objRoot);

            $success[$row['id']] = $this->doUpdate($row['id'], $objRoot, $strParentAlias, true, false);
        }

        return $success;
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
     * @param integer $intPageID   The page to be updated.
     * @param string  $strFragment The fragment to be used.
     *
     * @return boolean Whether the alias could be successfully updated.
     */
    public function update($intPageID, $strFragment = null, $blnAutoInherit = false): bool
    {
        if ($strFragment !== null) {
            $strFragment = strval($strFragment);
            if (!strlen($strFragment)) {
                return false;
            }
            $this->storeAlias($intPageID, $strFragment, $strFragment);
        }

        $objRoot        = $this->getRootPage($intPageID);
        $strParentAlias = $this->getParentAlias($intPageID, $objRoot);
        $blnUpdateAll   = $objRoot && $objRoot->id == $intPageID;

        return $this->doUpdate($intPageID, $objRoot, $strParentAlias, $blnUpdateAll, $blnAutoInherit);
    }

    protected function doUpdate($intPageID, $objRoot, $strParentAlias, $blnUpdateAll, $blnAutoInherit): bool
    {
        $strQuery = <<<'EOT'
SELECT 	id, pid, alias, type,
		bbit_turl_fragment,
		bbit_turl_inherit,
		bbit_turl_transparent,
		bbit_turl_ignoreRoot,
		bbit_turl_rootInherit
FROM	tl_page
WHERE	id = ?
EOT;
        $result = $this->connection->executeQuery($strQuery, [$intPageID]);
        $pageResult = (object) $result->fetchAssociative();

        if ($result->rowCount() === 0) {
            return false;
        }

        $strAlias = $strFragment = $this->prepareFragment($pageResult, $objRoot, $strParentAlias);

        if ($pageResult->type == 'root') {
            // updating a root page:
            // - do not check inheriting
            // - set new root page for nested updates
            // - reset parent alias for nested updates
            $objRoot        = $pageResult;
            $strParentAlias = null;
        } else {
            // updating a normal page:
            $blnInherit = $pageResult->bbit_turl_inherit;

            if ($objRoot && !$pageResult->bbit_turl_ignoreRoot) {
                switch ($objRoot->bbit_turl_rootInherit) {
                    default:
                    case 'normal':
                        $pageResult->pid == $objRoot->id && $strParentAlias = $objRoot->alias;
                        break;

                    case 'always':
                        $strRootAlias = $objRoot->alias;
                        break;

                    case 'never':
                        break;
                }
            }

            if ($blnAutoInherit && !$blnInherit) {
                $strUnprefixed = self::unprefix($strFragment, $strParentAlias);
                $blnInherit    = $strUnprefixed != $strFragment;
                $strFragment   = $strUnprefixed;
            }

            $strPrefix = $blnInherit ? trim($strRootAlias . '/' . $strParentAlias, '/') : $strRootAlias;
            $strAlias  = trim($strPrefix . '/' . $strFragment, '/');


            if (!$pageResult->bbit_turl_transparent) {
                $strParentAlias = $blnInherit ? trim($strParentAlias . '/' . $strFragment, '/') : $strFragment;
            }
        }

        $this->storeAlias($intPageID, $strAlias, $strFragment, $blnInherit);

        if (!$blnUpdateAll && !$blnAutoInherit && $objRoot && $objRoot->bbit_turl_rootInherit != 'always') {
            $strOnlyInherit = 'AND bbit_turl_inherit = \'1\'';
        }
        $strQuery    = <<<EOT
SELECT	id
FROM	tl_page
WHERE	pid = ?
AND		type != 'root'
$strOnlyInherit
EOT;

        $childrenResult = $this->connection->executeQuery($strQuery, [$intPageID]);

        while ($childId = $childrenResult->fetchOne()) {
            $this->doUpdate($childId, $objRoot, $strParentAlias, $blnUpdateAll, $blnAutoInherit);
        }

        return true;
    }

    protected function prepareFragment($objPage, $objRoot, $strParentAlias = null)
    {
        // use stored fragment
        $strFragment = $objPage->bbit_turl_fragment;
        if (strlen($strFragment)) {
            return $strFragment;
        }

        // create fragment from existing alias
        $strFragment = $objPage->alias;
        // remove root alias, if obeyed, according to inherit settings
        if ($objRoot && !$objPage->bbit_turl_ignoreRoot) {
            switch ($objRoot->bbit_turl_rootInherit) {
                default:
                case 'normal': // if root page is direct parent, use its alias as parent alias
                    $objPage->pid == $objRoot->id && $strParentAlias = $objRoot->alias;
                    break;

                case 'always': // always unprefix
                    $strFragment = self::unprefix($strFragment, $objRoot->alias);
                    break;

                case 'never':
                    break;
            }
        }
        // remove parent alias, if inheriting is enabled
        if ($objPage->bbit_turl_inherit) {
            $strFragment = self::unprefix($strFragment, $strParentAlias);
        }
        if (strlen($strFragment)) {
            return $strFragment;
        }

        return $this->makeAlias($objPage->id);
    }

    private function storeAlias($intPageID, $strAlias, $strFragment, $blnInherit = null): void
    {
        $set = [
            'alias' => $strAlias,
            'bbit_turl_fragment' => $strFragment
        ];

        if ($blnInherit !== null) {
            $set['bbit_turl_inherit'] = $blnInherit ? 1 : '';
        }

        $this->connection->update('tl_page', $set, ['id' => $intPageID]);
    }

    /**
     * Get the alias of the nearest ancestor page of the given page id,
     * that is not a root page and is not transparent in alias inheritance
     * hierarchy.
     *
     * @param integer $intPageID
     *
     * @return string
     */
    public function getParentAlias($intPageID, $objRoot = null)
    {
        $objRoot || $objRoot = $this->getRootPage($intPageID);

        do {
            $strQuery  = <<<EOT
SELECT	p2.id, p2.alias, p2.bbit_turl_transparent
FROM	tl_page AS p1
JOIN	tl_page AS p2 ON p2.id = p1.pid
WHERE	p1.id = ?
AND		p2.type != 'root'
EOT;
            $result = $this->connection->executeQuery($strQuery, [$intPageID]);
            $parent = (object) $result->fetchAssociative();
            $intPageID = $parent->id;
            if ($result->rowCount() === 0 || !$intPageID) {
                return '';
            }
        } while ($parent->bbit_turl_transparent);

        $strAlias = (string) $parent->alias;

        if ($objRoot && !$parent->bbit_turl_ignoreRoot) {
            switch ($objRoot->bbit_turl_rootInherit) {
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
     * page or does not exist, the root page is retrieved via
     * PageModel::findWithDetails() and the root page references within that
     * root page are repaired.
     *
     * @param int $intPageID
     *
     * @return object
     */
    public function getRootPage($intPageID)
    {
        $this->framework->initialize();

        $strQuery  = <<<EOT
SELECT	rt.id, rt.type, rt.alias, rt.bbit_turl_rootInherit
FROM	tl_page AS p
JOIN	tl_page AS rt ON rt.id = p.bbit_turl_root
WHERE	p.id = ?
EOT;
        $result = $this->connection->executeQuery($strQuery, [$intPageID]);
        $objRoot = (object) $result->fetchAssociative();

        if ($result->rowCount() > 0 && $objRoot->type == 'root') {
            return $objRoot;
        }

        $objPage = PageModel::findWithDetails($intPageID);
        if ($objPage->type == 'root') {
            $intRootID = $objPage->id;
        } elseif ($objPage->rootId) {
            $intRootID = $objPage->rootId;
        } else {
            return null;
        }

        $this->regeneratePageRoots([$intRootID]);
        $strQuery = <<<EOT
SELECT	id, alias, bbit_turl_rootInherit
FROM	tl_page
WHERE	id = ?
EOT;

        return (object) $this->connection->executeQuery($strQuery, [$intRootID])->fetchAssociative();
    }

    /**
     * This is a simple alias generation function, which ALWAYS generates an
     * alias for the given page id.
     *
     * @param integer $intPageID
     *
     * @return string
     */
    public function makeAlias($intPageID): string
    {
        $this->framework->initialize();

        $objPage = PageModel::findWithDetails($intPageID);

        if (!$objPage) {
            return 'page-' . $intPageID;
        }

        $strAlias = StringUtil::standardize($objPage->title);
        $strQuery = <<<EOT
SELECT	id
FROM	tl_page
WHERE	id != ?
AND		alias = ?
EOT;
        $result = $this->connection->executeQuery($strQuery, [$intPageID, $strAlias]);

        if ($result->rowCount() > 0) {
            $strAlias .= '-' . $intPageID;
        }

        return $strAlias;
    }

    public function splitAlias($arrPage)
    {
        $strAlias = $arrPage['alias'];
        if (!strlen($strAlias)) {
            return null;
        }

        $arrAlias = [];
        if ($arrPage['type'] === 'root') {
            switch ($arrPage['bbit_turl_rootInherit']) {
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

        if (!$arrPage['bbit_turl_ignoreRoot']) {
            $objRoot = $this->getRootPage($arrPage['id']);

            if ($objRoot) switch ($objRoot->bbit_turl_rootInherit) {
                case 'always':
                    $intLength = strlen($objRoot->alias);
                    if ($intLength && strncasecmp($strAlias, $objRoot->alias, $intLength) == 0) {
                        $arrAlias['root'] = $objRoot->alias;
                        $strAlias         = substr($strAlias, $intLength + 1);
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

        if (!$arrPage['bbit_turl_inherit']) {
            $arrAlias['fragment'] = $strAlias;

            return $arrAlias;
        }

        $intLength = strlen($arrPage['bbit_turl_fragment']);
        if (!$intLength) {
            $arrAlias['fragment']             = $strAlias;
            $arrAlias['err']['errNoFragment'] = true;

            return $arrAlias;
        }

        $strFragment = substr($strAlias, -$intLength);
        if ($strFragment != $arrPage['bbit_turl_fragment']) {
            $arrAlias['fragment']                  = $strAlias;
            $arrAlias['err']['errInvalidFragment'] = true;

            return $arrAlias;
        }

        $arrAlias['parent']   = trim(substr($strAlias, 0, -$intLength), '/');
        $arrAlias['fragment'] = $strFragment;

        return $arrAlias;
    }

    public static function unprefix($strAlias, $strPrefix)
    {
        return strlen($strPrefix) && self::isPrefix($strAlias, $strPrefix) ? substr(
            $strAlias,
            strlen($strPrefix) + 1
        ) : $strAlias;
    }

    public static function isPrefix($strAlias, $strPrefix)
    {
        $intLength = strlen($strPrefix);

        return !$intLength || strncmp($strAlias, $strPrefix . '/', $intLength + 1) == 0;
    }

    public function configurePageDetails(array $parents, PageModel $pageModel): void
    {
        $rootPage                = $this->getRootPage($pageModel->id);
        $pageModel->useFolderUrl = $this->determineUseFolderUrl($parents, $pageModel);

        if ($pageModel->bbit_turl_inherit) {
            $folderUrl = $this->getParentAlias($pageModel->id);
            if ($rootPage && $pageModel->bbit_turl_ignoreRoot) {
                $folderUrl = self::unprefix($folderUrl, $rootPage->alias);
            }
        } elseif ($rootPage && $rootPage->bbit_turl_rootInherit === 'always') {
            $folderUrl = $rootPage->alias;
        }

        $pageModel->folderUrl = $folderUrl . '/';
    }

    private function determineUseFolderUrl(array $parents, PageModel $pageModel): bool
    {
        if ($pageModel->bbit_turl_ignoreRoot) {
            return (bool) $pageModel->bbit_turl_inherit;
        }

        $rootPage = $this->getRootPage($pageModel->id);

        if ($rootPage && $rootPage->bbit_turl_rootInherit === 'always') {
            return true;
        }

        return (bool) $pageModel->bbit_turl_inherit;
    }
}
