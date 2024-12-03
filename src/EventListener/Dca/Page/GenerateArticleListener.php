<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\EventListener\DataContainer\ContentCompositionListener;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\BooleanType;
use ReflectionMethod;

use function array_pop;
use function explode;
use function is_array;
use function method_exists;

final class GenerateArticleListener
{
    public function __construct(
        private readonly ContentCompositionListener $listener,
        private readonly Connection $connection,
    ) {
    }

    /** @SuppressWarnings(PHPMD.Superglobals) */
    #[AsCallback('tl_page', 'config.onload')]
    public function onLoad(): void
    {
        if (! isset($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'])) {
            return;
        }

        foreach ($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'] as $index => $callback) {
            if (! is_array($callback) || $callback !== [ContentCompositionListener::class, 'generateArticleForPage']) {
                continue;
            }

            unset($GLOBALS['TL_DCA']['tl_page']['config']['onsubmit_callback'][$index]);
            break;
        }
    }

    #[AsCallback('tl_page', 'config.onsubmit', priority: 128)]
    public function onSubmit(DataContainer $dataContainer): void
    {
        if (! $dataContainer->activeRecord) {
            return;
        }

        $strAlias = $dataContainer->activeRecord->alias;
        $arrAlias = explode('/', $strAlias);

        $dataContainer->activeRecord->alias = array_pop($arrAlias);

        $row     = (array) $dataContainer->activeRecord;
        $columns = $this->connection->createSchemaManager()->listTableColumns('tl_article');

        if (isset($columns['published']) && $columns['published']->getType() instanceof BooleanType) {
            $row['published'] = $row['published'] ? 1 : 0;
        }

        if (method_exists($dataContainer, 'setCurrentRecordCache')) {
            $reflection = new ReflectionMethod($dataContainer, 'setCurrentRecordCache');
            $reflection->setAccessible(true);
            $reflection->invoke(
                $dataContainer,
                $dataContainer->id,
                $dataContainer->table,
                $row,
            );
        }

        $this->listener->generateArticleForPage($dataContainer);
        $dataContainer->activeRecord->alias = $strAlias;
    }
}
