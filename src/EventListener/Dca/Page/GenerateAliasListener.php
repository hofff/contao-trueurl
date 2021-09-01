<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\EventListener\DataContainer\PageUrlListener;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;
use Hofff\Contao\TrueUrl\TrueURL;

use function trim;

final class GenerateAliasListener
{
    private PageUrlListener $pageUrlListener;

    private TrueURL $trueUrl;

    public function __construct(PageUrlListener $pageUrlListener, TrueURL $trueUrl)
    {
        $this->pageUrlListener = $pageUrlListener;
        $this->trueUrl         = $trueUrl;
    }

    /** @Callback(table="tl_page", target="config.onsubmit", priority=128) */
    public function onSubmit(DataContainer $dataContainer): void
    {
        if (!$dataContainer->activeRecord) {
            return;
        }

        $alias = $dataContainer->activeRecord->alias;
        if ($alias === '') {
            $alias = $this->pageUrlListener->generateAlias('', $dataContainer);
        }
        $alias = trim($alias, '/');

        if ($alias === '') {
            return;
        }

        $fragment = $this->trueUrl->extractFragment($dataContainer->id, $alias);
        $this->trueUrl->update($dataContainer->id, $fragment);
    }
}
