<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Hofff\Contao\TrueUrl\HofffContaoTrueUrlBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

final class Plugin implements BundlePluginInterface, ConfigPluginInterface, RoutingPluginInterface
{
    /** {@inheritDoc} */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(HofffContaoTrueUrlBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class])
                ->setReplace(['backboneit_trueurl']),
        ];
    }

    /** {@inheritDoc} */
    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader->load(__DIR__ . '/../Resources/config/config.yaml');
    }

    /** @SuppressWarnings(PHPMD.UnusedFormalParameter) */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): RouteCollection|null
    {
        $loader = $resolver->resolve(__DIR__ . '/../Resources/config/routes.xml');
        if (! $loader) {
            return null;
        }

        return $loader->load(__DIR__ . '/../Resources/config/routes.xml');
    }
}
