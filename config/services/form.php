<?php

declare(strict_types=1);

use Sylius\Bundle\AdminBundle\Form\Type\ChannelType;
use Sylius\LiveOnePageCheckoutPlugin\Form\Extension\Admin\ChannelTypeExtension;
use Sylius\LiveOnePageCheckoutPlugin\Form\Type\CheckoutType;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $container->parameters()
        ->set(
            'sylius_live_one_page_checkout.form.type.checkout.validation_groups',
            [
                'sylius',
                'sylius_checkout_complete'
            ]
        )
    ;

    $services->set('sylius_live_one_page_checkout.form.extension.type.admin.channel', ChannelTypeExtension::class)
        ->tag('form.type_extension', ['extended_type' => ChannelType::class])
    ;

    $container->services()
        ->set('sylius_live_one_page_checkout.form.type.checkout', CheckoutType::class)
        ->args([
            param('sylius.model.order.class'),
            param('sylius_live_one_page_checkout.form.type.checkout.validation_groups'),
            service('sylius.repository.address'),
            tagged_iterator('sylius_shop.modifier.address_form_values'),
            service('sylius.comparator.address'),
        ])
        ->tag('form.type')
    ;
};
