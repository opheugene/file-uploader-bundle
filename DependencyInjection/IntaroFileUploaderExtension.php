<?php

namespace Intaro\FileUploaderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class IntaroFileUploaderExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container)
    {
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration(new Configuration(), $configs);

        $gaufretteConfig = $this->generateGaufretteConfig($config);
        $container->prependExtensionConfig('knp_gaufrette', $gaufretteConfig);

        if (!isset($config['uploaders'])) {
            return;
        }

        foreach ($config['uploaders'] as $uploaderType => $uploaders) {
            foreach ($uploaders as $name => $options) {
                $container->setDefinition("intaro.{$name}_uploader",
                    new Definition(
                    '%intaro_file_uploader.class%',
                    [
                        new Reference("gaufrette.{$name}_filesystem"),
                        new Reference("router"),
                        $options['path'],
                        $options['allowed_types']
                    ]
                ));
            }
        }
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    protected function generateGaufretteConfig($config)
    {
        $filesystems = [];
        $adapters = [];
        
        if (isset($config['uploaders'])) {
            foreach (array_filter($config['uploaders']) as $uploaderType => $uploaders) {
                foreach ($uploaders as $name => $options) {
                    unset(
                        $options['allowed_types'],
                        $options['path']
                    );
                    $filesystems[$name] = [
                        'adapter' => $name
                    ];
                    $adapters[$name] = [
                        $uploaderType => $options
                    ];
                }
            }
        }
        
        $gaufretteConfig = [
            'adapters' => $adapters,
            'filesystems' => $filesystems
        ];

        return $gaufretteConfig;
    }
}
