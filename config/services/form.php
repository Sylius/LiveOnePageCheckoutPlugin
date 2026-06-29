<?php

declare(strict_types=1);

use Sylius\Bundle\AdminBundle\Form\Type\ChannelType;
use Sylius\LiveOnePageCheckoutPlugin\Form\Extension\Admin\ChannelTypeExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_live_one_page_checkout.form.extension.type.admin.channel', ChannelTypeExtension::class)
        ->tag('form.type_extension', ['extended_type' => ChannelType::class])
    ;
};
