<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Hook;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\PageModel;
use Hofff\Contao\TrueUrl\TrueURL;

/** @Hook("loadPageDetails") */
final class PageDetailsListener
{
    private TrueURL $trueUrl;

    public function __construct(TrueURL $trueUrl)
    {
        $this->trueUrl = $trueUrl;
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
