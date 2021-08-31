<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Hook;

use Contao\PageModel;

final class PageDetailsListener
{
    /** @param list<PageModel> $parents */
    public function __invoke(array $parents, PageModel $pageModel): void
    {
        $pageModel->useFolderUrl = false;
    }
}
