<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Contao\PageModel;
use Doctrine\DBAL\Connection;

use function strlen;

final class RootInheritListener
{
    private Connection $connection;

    private array $changedValues = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /** @Callback(table="tl_page", target="config.oncreate", priority=128) */
    public function onCreate(string $table, $recordId, array $set, DataContainer $dataContainer): void
    {
        if (!$set['pid']) {
            return;
        }

        $parentPage = PageModel::findWithDetails($set['pid']);
        if (!$parentPage) {
            $this->connection->update($table, ['bbit_turl_root' => 0], ['id' => $recordId]);

            return;
        }

        $rootId = $parentPage->type === 'root' ? $parentPage->id : $parentPage->rootId;

        if (! $rootId) {
            $this->connection->update($table, ['bbit_turl_root' => 0], ['id' => $recordId]);

            return;
        }

        $strQuery          = <<<'EOT'
SELECT	bbit_turl_defaultInherit
FROM	tl_page
WHERE	id = ?
EOT;
        $result = $this->connection->executeQuery($strQuery, [$rootId]);
        if ($result->rowCount() === 0) {
            $this->connection->update($table, ['bbit_turl_root' => 0], ['id' => $recordId]);
            return;
        }

        $this->connection->update(
            $table,
            ['bbit_turl_root' => $rootId, 'bbit_turl_inherit' => $result->fetchOne()],
            ['id' => $recordId]
        );
    }

    /** @Callback(table="tl_page", target="fields.bbit_turl_rootInheritProxy.load") */
    public function onLoad($varValue, DataContainer $dataContainer)
    {
        $varValue = $dataContainer->activeRecord->bbit_turl_rootInherit;

        return $varValue ? $varValue : 'normal';
    }

    /** @Callback(table="tl_page", target="fields.bbit_turl_rootInheritProxy.save") */
    public function onSave($newValue, DataContainer $dataContainer)
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

    /** @Callback(table="tl_page", target="config.onsubmit") */
    public function onSubmit(DataContainer $dataContainer)
    {
        if (!isset($this->changedValues[$dataContainer->id])) {
            return;
        }

        if ($dataContainer->activeRecord->type != 'root') {
            unset($this->changedValues[$dataContainer->id]);

            return;
        }

        $newValue = $this->changedValues[$dataContainer->id];
        unset($this->changedValues[$dataContainer->id]);

        $strQuery = <<<EOT
UPDATE	tl_page
SET		bbit_turl_rootInherit = ?
WHERE	id = ?
EOT;

        $this->connection->executeStatement($strQuery, [$newValue, $dataContainer->id]);

        $strAlias = $dataContainer->activeRecord->alias;
        if ($newValue !== 'always' || !strlen($strAlias)) {
            return;
        }

        // remove the root alias from fragments of all pages,
        // where the alias consists only of the fragment
        // and that do not ignore the root alias
        $strQuery = <<<'EOT'
UPDATE	tl_page
SET		bbit_turl_fragment = SUBSTRING(bbit_turl_fragment, ?)
WHERE	bbit_turl_root = ?
AND		bbit_turl_fragment LIKE ?
AND		bbit_turl_fragment = alias
AND		bbit_turl_ignoreRoot = ''
EOT;

        $this->connection->executeStatement($strQuery, [strlen($strAlias) + 2, $dataContainer->id, $strAlias . '/%']);
    }
}
