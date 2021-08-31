<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Hofff\Contao\TrueUrl\HofffContaoTrueUrlBundle;

final class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(HofffContaoTrueUrlBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['backboneit_trueurl'])
        ];
    }
}
