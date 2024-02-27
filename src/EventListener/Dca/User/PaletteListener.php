<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\User;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

use function is_string;

#[AsCallback('tl_user', 'config.onload')]
final class PaletteListener
{
    /** @SuppressWarnings(PHPMD.Superglobals) */
    public function __invoke(): void
    {
        $manipulator = PaletteManipulator::create()
            ->addField('hofff_trueurl_view_mode', 'backend_legend', PaletteManipulator::POSITION_APPEND);

        foreach ($GLOBALS['TL_DCA']['tl_user']['palettes'] as $name => $palette) {
            if (! is_string($palette)) {
                continue;
            }

            $manipulator->applyToPalette($name, 'tl_user');
        }
    }
}
