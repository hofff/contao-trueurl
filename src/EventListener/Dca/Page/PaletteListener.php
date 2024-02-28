<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\EventListener\Dca\Page;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

use function is_string;

#[AsCallback('tl_page', 'config.onload')]
final class PaletteListener
{
    /** @SuppressWarnings(PHPMD.Superglobals) */
    public function __invoke(): void
    {
        $rootManipulator = PaletteManipulator::create()->addField(
            ['bbit_turl_rootInheritProxy', 'bbit_turl_defaultInherit'],
            'routing_legend',
            PaletteManipulator::POSITION_APPEND,
        );

        $pageManipulator = PaletteManipulator::create()->addField(
            ['bbit_turl_inherit', 'bbit_turl_transparent', 'bbit_turl_ignoreRoot'],
            'routing_legend',
            PaletteManipulator::POSITION_APPEND,
        );

        foreach ($GLOBALS['TL_DCA']['tl_page']['palettes'] as $selector => $palette) {
            if ($selector === '__selector__' || ! is_string($palette)) {
                continue;
            }

            if ($selector === 'root' || $selector === 'rootfallback') {
                $rootManipulator->applyToPalette($selector, 'tl_page');
                continue;
            }

            if ($selector === 'folder') {
                $pageManipulator->addLegend('routing_legend', 'title_legend');
                $pageManipulator->addField('alias', 'routing_legend', PaletteManipulator::POSITION_PREPEND);
            }

            $pageManipulator->applyToPalette($selector, 'tl_page');
        }
    }
}
