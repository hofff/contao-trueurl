<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Hook;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Hofff\Contao\TrueUrl\EventListener\Dca\Page\GenerateLabelListener;

#[AsHook('loadDataContainer')]
final class LoadDataContainerListener
{
    /** @SuppressWarnings(PHPMD.Superglobals) */
    public function __invoke(string $tableName): void
    {
        if ($tableName !== 'tl_page') {
            return;
        }

        $dca                                    = &$GLOBALS['TL_DCA']['tl_page'];
        $dca['list']['label']['bbit_turl']      = $dca['list']['label']['label_callback'];
        $dca['list']['label']['label_callback'] = [GenerateLabelListener::class, '__invoke'];
    }
}
