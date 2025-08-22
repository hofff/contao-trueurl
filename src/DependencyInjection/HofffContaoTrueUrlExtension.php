<?php

declare(strict_types=1);

namespace Hofff\Contao\TrueUrl\DependencyInjection;

use Override;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class HofffContaoTrueUrlExtension extends Extension
{
    /**
     * @param list<array<string,mixed>> $configs
     *
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    #[Override]
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config'),
        );

        $config = $this->processConfiguration(new Configuration(), $configs);
        $container->setParameter('hofff_contao_true_url.unrouteable_page_types', $config['unrouteable_page_types']);

        $loader->load('listeners.xml');
        $loader->load('services.xml');
    }
}
