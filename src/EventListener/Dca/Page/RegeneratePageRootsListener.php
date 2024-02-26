<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Hofff\Contao\TrueUrl\TrueURL;

final class RegeneratePageRootsListener
{
    public function __construct(private readonly TrueURL $trueUrl)
    {
    }

    /** @Callback(table="tl_page", target="config.oncopy", priority=255) */
    public function onCopy(int|string $recordId): void
    {
        $this->trueUrl->regeneratePageRoots([$recordId]);
        $this->trueUrl->update((int) $recordId);
    }

    /** @Callback(table="tl_page", target="config.oncut", priority=255) */
    public function onCut(DataContainer $dataContainer): void
    {
        $this->trueUrl->regeneratePageRoots([$dataContainer->id]);
        $this->trueUrl->update((int) $dataContainer->id);
    }

    /**
     * @Callback(table="tl_page", target="config.onrestore_version", priority=255)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onRestoreVersion(string $table, int|string $recordId): void
    {
        $this->trueUrl->regeneratePageRoots([$recordId]);
        $this->trueUrl->update((int) $recordId);
    }

    /**
     * @param array<string,mixed> $record
     *
     * @Callback(table="tl_page", target="config.onundo", priority=255)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function onUndo(string $table, array $record): void
    {
        $this->trueUrl->regeneratePageRoots([$record['id']]);
        $this->trueUrl->update((int) $record['id']);
    }
}
