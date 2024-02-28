<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Hook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\PageModel;
use Hofff\Contao\TrueUrl\TrueURL;

#[AsHook('loadPageDetails')]
final class PageDetailsListener
{
    public function __construct(private readonly TrueURL $trueUrl)
    {
    }

    /**
     * @param list<PageModel> $parents
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __invoke(array $parents, PageModel $pageModel): void
    {
        $this->trueUrl->configurePageDetails($pageModel);
    }
}
