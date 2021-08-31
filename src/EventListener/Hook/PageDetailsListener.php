<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Hook;

use Contao\PageModel;
use Hofff\Contao\TrueUrl\TrueURL;

final class PageDetailsListener
{
    private TrueURL $trueUrl;

    public function __construct(TrueURL $trueUrl)
    {
        $this->trueUrl = $trueUrl;
    }

    /** @param list<PageModel> $parents */
    public function __invoke(array $parents, PageModel $pageModel): void
    {
        $pageModel->folderUrl = $this->trueUrl->getParentAlias($pageModel->id) . '/';
    }
}
