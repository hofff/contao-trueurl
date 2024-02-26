<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Database;
use Contao\PageModel;
use Contao\StringUtil;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use InvalidArgumentException;

use function array_combine;
use function array_filter;
use function array_map;
use function array_merge;
use function array_unique;
use function strlen;
use function strncasecmp;
use function strncmp;
use function substr;
use function trim;

/** @SuppressWarnings(PHPMD.ExcessiveClassComplexity) */
final class TrueURL
{
    public function __construct(private readonly Connection $connection, private readonly ContaoFramework $framework)
    {
    }

    /**
     * Regenerate the direct root page relation.
     *
     * @param list<string|int>|null $pageIds
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function regeneratePageRoots(array|null $pageIds = null, bool $orphans = true): void
    {
        $this->framework->initialize();
        $database = $this->framework->createInstance(Database::class);

        if ($pageIds !== null) {
            $pageIds = array_unique(array_map('intval', array_filter($pageIds, 'is_numeric')));
            $rootIds = [];
            foreach ($pageIds as $pageId) {
                $pageModel = PageModel::findWithDetails($pageId);
                if (! $pageModel) {
                    continue;
                }

                $intRoot             = $pageModel->type === 'root' ? (int) $pageModel->id : (int) $pageModel->rootId;
                $rootIds[$intRoot][] = (int) $pageModel->id;
            }
        } else {
            $query   = <<<'EOT'
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
                ['descendants' => ArrayParameterType::STRING],
            );
        }

        if (! $orphans) {
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
                ->executeQuery($query, ['pids' => $arrPIDs], ['pids' => ArrayParameterType::STRING])
                ->fetchFirstColumn();

            $arrIDs[] = $arrPIDs;
        }

        $arrIDs = array_merge(...$arrIDs);

        if (! $arrIDs) {
            return;
        }

        $query = <<<'EOT'
UPDATE  tl_page
SET     bbit_turl_root = 0
WHERE   id IN (:ids)
EOT;

        $this->connection->executeQuery($query, ['ids' => $arrIDs], ['ids' => ArrayParameterType::STRING]);
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
     * @throws InvalidArgumentException If $strAlias casts to an empty string.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function extractFragment(int $pageId, string $alias): string
    {
        $fragment = $alias;
        if ($fragment === '') {
            throw new InvalidArgumentException('Argument #2 must be a non-empty string');
        }

        $query      = <<<'EOT'
SELECT 	id, pid, type, bbit_turl_inherit, bbit_turl_ignoreRoot
FROM	tl_page
WHERE	id = ?
EOT;
        $statement  = $this->connection->executeQuery($query, [$pageId]);
        $pageResult = (object) $statement->fetchAssociative();

        if ($statement->rowCount() === 0 || $pageResult->type === 'root') {
            return $fragment;
        }

        $rootPage    = $this->getRootPage($pageId);
        $parentAlias = null;

        if ($rootPage && ! $pageResult->bbit_turl_ignoreRoot) {
            switch ($rootPage->bbit_turl_rootInherit) {
                default:
                case 'normal':
                    $pageResult->pid === $rootPage->id && $parentAlias = $rootPage->alias;
                    break;

                case 'always':
                    $fragment = self::unprefix($fragment, $rootPage->alias);
                    break;

                case 'never':
                    break;
            }
        }

        if ($pageResult->bbit_turl_inherit) {
            if ($parentAlias === null) {
                $parentAlias = $this->getParentAlias($pageId, $rootPage);
            }

            $fragment = self::unprefix($fragment, $parentAlias);
        }

        return $fragment;
    }

    /**
     * Repair the aliases of all pages.
     *
     * Returns an array of succes state for reach root page id.
     *
     * @return array<int|string, bool>
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function repair(): array
    {
        $query     = <<<'EOT'
SELECT	id
FROM	tl_page
WHERE	type = 'root'
EOT;
        $statement = $this->connection->executeQuery($query);
        $success   = [];

        while ($row = $statement->fetchAssociative()) {
            $objRoot        = $this->getRootPage((int) $row['id']);
            $strParentAlias = $this->getParentAlias((int) $row['id'], $objRoot);

            $success[$row['id']] = $this->doUpdate((int) $row['id'], $objRoot, $strParentAlias, true, false);
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
     * @param int    $pageId   The page to be updated.
     * @param string $fragment The fragment to be used.
     *
     * @return bool Whether the alias could be successfully updated.
     */
    public function update(int $pageId, string|null $fragment = null, bool $autoInherit = false): bool
    {
        if ($fragment !== null) {
            if ($fragment === '') {
                return false;
            }

            $this->storeAlias($pageId, $fragment, $fragment);
        }

        $rootPage    = $this->getRootPage($pageId);
        $parentAlias = $this->getParentAlias($pageId, $rootPage);
        $updateAll   = $rootPage && $rootPage->id === $pageId;

        return $this->doUpdate($pageId, $rootPage, $parentAlias, $updateAll, $autoInherit);
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function doUpdate(
        int $pageId,
        object|null $rootPage,
        string|null $parentAlias,
        bool $updateAll,
        bool $autoInherit,
    ): bool {
        $query      = <<<'EOT'
SELECT 	id, pid, alias, type,
		bbit_turl_fragment,
		bbit_turl_inherit,
		bbit_turl_transparent,
		bbit_turl_ignoreRoot,
		bbit_turl_rootInherit
FROM	tl_page
WHERE	id = ?
EOT;
        $result     = $this->connection->executeQuery($query, [$pageId]);
        $pageResult = (object) $result->fetchAssociative();

        if ($result->rowCount() === 0) {
            return false;
        }

        $strAlias     = $fragment = $this->prepareFragment($pageResult, $rootPage, $parentAlias);
        $inherit      = false;
        $strRootAlias = '';

        if ($pageResult->type === 'root') {
            // updating a root page:
            // - do not check inheriting
            // - set new root page for nested updates
            // - reset parent alias for nested updates
            $rootPage    = $pageResult;
            $parentAlias = null;
        } else {
            // updating a normal page:
            $inherit = (bool) $pageResult->bbit_turl_inherit;

            if ($rootPage && ! $pageResult->bbit_turl_ignoreRoot) {
                switch ($rootPage->bbit_turl_rootInherit) {
                    default:
                    case 'normal':
                        $pageResult->pid === $rootPage->id && $parentAlias = $rootPage->alias;
                        break;

                    case 'always':
                        $strRootAlias = $rootPage->alias;
                        break;

                    case 'never':
                        break;
                }
            }

            if ($autoInherit && ! $inherit) {
                $unprefixed = self::unprefix($fragment, $parentAlias);
                $inherit    = $unprefixed !== $fragment;
                $fragment   = $unprefixed;
            }

            $strPrefix = $inherit ? trim($strRootAlias . '/' . $parentAlias, '/') : $strRootAlias;
            $strAlias  = trim($strPrefix . '/' . $fragment, '/');

            if (! $pageResult->bbit_turl_transparent) {
                $parentAlias = $inherit ? trim($parentAlias . '/' . $fragment, '/') : $fragment;
            }
        }

        $this->storeAlias($pageId, $strAlias, $fragment, $inherit);

        $query = <<<'EOT'
SELECT	id
FROM	tl_page
WHERE	pid = ?
AND		type != 'root';
EOT;
        if (! $updateAll && ! $autoInherit && $rootPage && $rootPage->bbit_turl_rootInherit !== 'always') {
            $query .= 'AND bbit_turl_inherit = \'1\'';
        }

        $childrenResult = $this->connection->executeQuery($query, [$pageId]);

        while ($childId = $childrenResult->fetchOne()) {
            $this->doUpdate((int) $childId, $rootPage, $parentAlias, $updateAll, $autoInherit);
        }

        return true;
    }

    /** @SuppressWarnings(PHPMD.CyclomaticComplexity) */
    private function prepareFragment(object $page, object|null $rootPage, string|null $parentAlias = null): string
    {
        // use stored fragment
        $strFragment = (string) $page->bbit_turl_fragment;
        if ($strFragment !== '') {
            return $strFragment;
        }

        // create fragment from existing alias
        $strFragment = $page->alias;
        // remove root alias, if obeyed, according to inherit settings
        if ($rootPage && ! $page->bbit_turl_ignoreRoot) {
            switch ($rootPage->bbit_turl_rootInherit) {
                default:
                case 'normal': // if root page is direct parent, use its alias as parent alias
                    $page->pid === $rootPage->id && $parentAlias = $rootPage->alias;
                    break;

                case 'always': // always unprefix
                    $strFragment = self::unprefix($strFragment, $rootPage->alias);
                    break;

                case 'never':
                    break;
            }
        }

        // remove parent alias, if inheriting is enabled
        if ($page->bbit_turl_inherit) {
            $strFragment = self::unprefix($strFragment, $parentAlias);
        }

        if ($strFragment !== '') {
            return $strFragment;
        }

        return $this->makeAlias((int) $page->id);
    }

    private function storeAlias(int $pageId, string $alias, string $fragment, bool|null $inherit = null): void
    {
        $set = [
            'alias' => $alias,
            'bbit_turl_fragment' => $fragment,
        ];

        if ($inherit !== null) {
            $set['bbit_turl_inherit'] = $inherit ? 1 : '';
        }

        $this->connection->update('tl_page', $set, ['id' => $pageId]);
    }

    /**
     * Get the alias of the nearest ancestor page of the given page id,
     * that is not a root page and is not transparent in alias inheritance
     * hierarchy.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getParentAlias(int $pageId, object|null $rootPage = null): string
    {
        $rootPage || $rootPage = $this->getRootPage($pageId);

        do {
            $strQuery = <<<'EOT'
SELECT	p2.id, p2.alias, p2.bbit_turl_transparent, p2.bbit_turl_ignoreRoot
FROM	tl_page AS p1
JOIN	tl_page AS p2 ON p2.id = p1.pid
WHERE	p1.id = ?
AND		p2.type != 'root'
EOT;
            $result   = $this->connection->executeQuery($strQuery, [$pageId]);
            $parent   = (object) $result->fetchAssociative();
            if ($result->rowCount() === 0 || ! $parent->id) {
                return '';
            }

            $pageId = $parent->id;
        } while ($parent->bbit_turl_transparent);

        $strAlias = (string) $parent->alias;

        if ($rootPage && ! $parent->bbit_turl_ignoreRoot) {
            switch ($rootPage->bbit_turl_rootInherit) {
                default:
                case 'always':
                    $strAlias = self::unprefix($strAlias, $rootPage->alias);
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
     */
    private function getRootPage(int $pageId): object|null
    {
        $pageModel = PageModel::findWithDetails($pageId);
        if ($pageModel === null) {
            return null;
        }

        return $this->getRootPageFromPageModel($pageModel);
    }

    private function getRootPageFromPageModel(PageModel $pageModel): object|null
    {
        $this->framework->initialize();

        $strQuery = <<<'EOT'
SELECT	rt.id, rt.type, rt.alias, rt.bbit_turl_rootInherit
FROM	tl_page AS p
JOIN	tl_page AS rt ON rt.id = p.bbit_turl_root
WHERE	p.id = ?
EOT;
        $result   = $this->connection->executeQuery($strQuery, [$pageModel->id]);
        $objRoot  = (object) $result->fetchAssociative();

        if ($result->rowCount() > 0 && $objRoot->type === 'root') {
            return $objRoot;
        }

        if ($pageModel->type === 'root') {
            $intRootID = $pageModel->id;
        } elseif ($pageModel->rootId) {
            $intRootID = $pageModel->rootId;
        } else {
            return null;
        }

        $strQuery = <<<'EOT'
SELECT	id, alias, bbit_turl_rootInherit
FROM	tl_page
WHERE	id = ?
EOT;

        $result = $this->connection->executeQuery($strQuery, [$intRootID]);
        if ($result->rowCount() === 0) {
            return null;
        }

        return (object) $result->fetchAssociative();
    }

    /**
     * This is a simple alias generation function, which ALWAYS generates an
     * alias for the given page id.
     */
    private function makeAlias(int $pageId): string
    {
        $this->framework->initialize();

        $objPage = PageModel::findWithDetails($pageId);

        if (! $objPage) {
            return 'page-' . $pageId;
        }

        $strAlias = StringUtil::standardize($objPage->title);
        $strQuery = <<<'EOT'
SELECT	id
FROM	tl_page
WHERE	id != ?
AND		alias = ?
EOT;
        $result   = $this->connection->executeQuery($strQuery, [$pageId, $strAlias]);

        if ($result->rowCount() > 0) {
            $strAlias .= '-' . $pageId;
        }

        return $strAlias;
    }

    /**
     * @param array<string,mixed> $page
     *
     * @return array<string,mixed>|null
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function splitAlias(array $page): array|null
    {
        $strAlias = $page['alias'];
        if (! strlen($strAlias)) {
            return null;
        }

        $arrAlias = [
            'root'   => null,
            'parent' => null,
            'err'    => null,
        ];
        if ($page['type'] === 'root') {
            switch ($page['bbit_turl_rootInherit']) {
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

        if (! $page['bbit_turl_ignoreRoot']) {
            $objRoot = $this->getRootPage((int) $page['id']);

            if ($objRoot) {
                switch ($objRoot->bbit_turl_rootInherit) {
                    case 'always':
                        $intLength = strlen($objRoot->alias);
                        if ($intLength && strncasecmp($strAlias, $objRoot->alias, $intLength) === 0) {
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
        }

        if (! $page['bbit_turl_inherit']) {
            $arrAlias['fragment'] = $strAlias;

            return $arrAlias;
        }

        $intLength = strlen($page['bbit_turl_fragment']);
        if (! $intLength) {
            $arrAlias['fragment']             = $strAlias;
            $arrAlias['err']['errNoFragment'] = true;

            return $arrAlias;
        }

        $strFragment = substr($strAlias, -$intLength);
        if ($strFragment !== $page['bbit_turl_fragment']) {
            $arrAlias['fragment']                  = $strAlias;
            $arrAlias['err']['errInvalidFragment'] = true;

            return $arrAlias;
        }

        $arrAlias['parent']   = trim(substr($strAlias, 0, -$intLength), '/');
        $arrAlias['fragment'] = $strFragment;

        return $arrAlias;
    }

    private static function unprefix(string $alias, string|null $prefix): string
    {
        return $prefix !== null && $prefix !== '' && self::isPrefix($alias, $prefix)
            ? substr($alias, strlen($prefix) + 1)
            : $alias;
    }

    private static function isPrefix(string $alias, string $prefix): bool
    {
        $length = strlen($prefix);

        return ! $length || strncmp($alias, $prefix . '/', $length + 1) === 0;
    }

    public function configurePageDetails(PageModel $pageModel): void
    {
        $folderUrl               = null;
        $rootPage                = $this->getRootPageFromPageModel($pageModel);
        $pageModel->useFolderUrl = $this->determineUseFolderUrl($pageModel);

        if ($pageModel->bbit_turl_inherit) {
            $folderUrl = $this->getParentAlias((int) $pageModel->id, $rootPage);
            if ($rootPage && $pageModel->bbit_turl_ignoreRoot) {
                $folderUrl = self::unprefix($folderUrl, $rootPage->alias);
            }
        } elseif ($rootPage && $rootPage->bbit_turl_rootInherit === 'always') {
            $folderUrl = $rootPage->alias;
        }

        $pageModel->folderUrl = $folderUrl === null ? '' : $folderUrl . '/';
    }

    private function determineUseFolderUrl(PageModel $pageModel): bool
    {
        if ($pageModel->bbit_turl_ignoreRoot) {
            return (bool) $pageModel->bbit_turl_inherit;
        }

        $rootPage = $this->getRootPageFromPageModel($pageModel);

        if ($rootPage && $rootPage->bbit_turl_rootInherit === 'always') {
            return true;
        }

        return (bool) $pageModel->bbit_turl_inherit;
    }
}
