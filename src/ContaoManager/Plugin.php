<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Hofff\Contao\TrueUrl\HofffContaoTrueUrlBundle;
use Override;
use Symfony\Component\Config\Loader\LoaderInterface;

final class Plugin implements BundlePluginInterface, ConfigPluginInterface
{
    /** {@inheritDoc} */
    #[Override]
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(HofffContaoTrueUrlBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['backboneit_trueurl']),
        ];
    }

    /** {@inheritDoc} */
    #[Override]
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(__DIR__ . '/../Resources/config/config.yaml');
    }
}
