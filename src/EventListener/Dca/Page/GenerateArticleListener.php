<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\EventListener\DataContainer\ContentCompositionListener;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DataContainer;

use function array_pop;
use function explode;
use function is_array;

final class GenerateArticleListener
{
    private ContentCompositionListener $listener;

    public function __construct(ContentCompositionListener $listener)
    {
        $this->listener = $listener;
    }

    /**
     * @Callback(table="tl_page", target="config.onload")
     * @SuppressWarnings(PHPMD.Superglobals)
     */
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

    /** @Callback(table="tl_page", target="config.onsubmit", priority=128) */
    public function onSubmit(DataContainer $dataContainer): void
    {
        if (! $dataContainer->activeRecord) {
            return;
        }

        $strAlias = $dataContainer->activeRecord->alias;
        $arrAlias = explode('/', $strAlias);

        $dataContainer->activeRecord->alias = array_pop($arrAlias);
        $this->listener->generateArticleForPage($dataContainer);
        $dataContainer->activeRecord->alias = $strAlias;
    }
}
