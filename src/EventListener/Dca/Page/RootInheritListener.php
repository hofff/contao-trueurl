<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\PageModel;
use Doctrine\DBAL\Connection;

use function strlen;

final class RootInheritListener
{
    /** @var array<int|string,string> */
    private array $changedValues = [];

    public function __construct(private readonly Connection $connection)
    {
    }

    /** @param array<string,mixed> $set */
    #[AsCallback('tl_page', 'config.oncreate', priority:128)]
    public function onCreate(string $table, string|int $recordId, array $set): void
    {
        if (! $set['pid']) {
            return;
        }

        $parentPage = PageModel::findWithDetails($set['pid']);
        if (! $parentPage instanceof PageModel) {
            return;
        }

        $rootId = $parentPage->type === 'root' ? $parentPage->id : $parentPage->rootId;
        $query  = <<<'EOT'
 SELECT	bbit_turl_defaultInherit
   FROM	tl_page
  WHERE	id = ?
EOT;
        $result = $this->connection->executeQuery($query, [$rootId]);
        if ($result->rowCount() === 0) {
            return;
        }

        $this->connection->update(
            $table,
            ['bbit_turl_inherit' => (string) $result->fetchOne()],
            ['id' => $recordId],
        );
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    #[AsCallback('tl_page', 'fields.bbit_turl_rootInheritProxy.load')]
    public function onLoad(mixed $varValue, DataContainer $dataContainer): mixed
    {
        if (! $dataContainer->activeRecord) {
            return 'normal';
        }

        return $dataContainer->activeRecord->bbit_turl_rootInherit ?: 'normal';
    }

    #[AsCallback('tl_page', 'fields.bbit_turl_rootInheritProxy.save')]
    public function onSave(mixed $newValue, DataContainer $dataContainer): mixed
    {
        if (! $dataContainer->activeRecord) {
            return null;
        }

        $oldValue = $dataContainer->activeRecord->bbit_turl_rootInherit ?: 'normal';

        if ($oldValue !== $newValue) {
            $this->changedValues[$dataContainer->id] = $newValue;
        }

        return null;
    }

    #[AsCallback('tl_page', 'config.onsubmit', priority: -1)]
    public function onSubmit(DataContainer $dataContainer): void
    {
        if (! isset($this->changedValues[$dataContainer->id]) || ! $dataContainer->activeRecord) {
            return;
        }

        if ($dataContainer->activeRecord->type !== 'root') {
            unset($this->changedValues[$dataContainer->id]);

            return;
        }

        $newValue = $this->changedValues[$dataContainer->id];
        unset($this->changedValues[$dataContainer->id]);

        $strQuery = <<<'EOT'
UPDATE	tl_page
SET		bbit_turl_rootInherit = ?
WHERE	id = ?
EOT;

        $this->connection->executeStatement($strQuery, [$newValue, $dataContainer->id]);

        $strAlias = $dataContainer->activeRecord->alias;
        if ($newValue !== 'always' || (string) $strAlias === '') {
            return;
        }

        // remove the root alias from fragments of all pages,
        // where the alias consists only of the fragment
        // and that do not ignore the root alias
        $strQuery = <<<'EOT'
UPDATE	tl_page
SET		bbit_turl_fragment = SUBSTRING(bbit_turl_fragment, ?)
WHERE	hofff_root_page_id = ?
AND		bbit_turl_fragment LIKE ?
AND		bbit_turl_fragment = alias
AND		bbit_turl_ignoreRoot = ''
EOT;

        $this->connection->executeStatement(
            $strQuery,
            [strlen((string) $strAlias) + 2, $dataContainer->id, $strAlias . '/%'],
        );
    }
}
