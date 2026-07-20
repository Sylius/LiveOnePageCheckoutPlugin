<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Sylius Sp. z o.o.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Sylius\Bundle\AdminBundle\Form\Type\ChannelType;
use Sylius\LiveOnePageCheckoutPlugin\Form\Extension\Admin\ChannelTypeExtension;
use Sylius\LiveOnePageCheckoutPlugin\Form\Type\CheckoutType;
use Sylius\LiveOnePageCheckoutPlugin\Form\Type\DefaultAddressChoiceType;
use Sylius\LiveOnePageCheckoutPlugin\Form\Type\PaymentType;
use Sylius\LiveOnePageCheckoutPlugin\Form\Type\ShipmentType;
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
                'sylius_checkout_complete',
            ]
        )
    ;

    $services->set('sylius_live_one_page_checkout.form.extension.type.admin.channel', ChannelTypeExtension::class)
        ->tag('form.type_extension', ['extended_type' => ChannelType::class])
    ;

    $services->set('sylius_live_one_page_checkout.form.type.shipment', ShipmentType::class)
        ->args([
            param('sylius.model.shipment.class'),
        ])
        ->tag('form.type')
    ;

    $services->set('sylius_live_one_page_checkout.form.type.payment', PaymentType::class)
        ->args([
            param('sylius.model.payment.class'),
        ])
        ->tag('form.type')
    ;

    $services->set('sylius_live_one_page_checkout.form.type.default_address_choice', DefaultAddressChoiceType::class)
        ->tag('form.type')
    ;

    $container->services()
        ->set('sylius_live_one_page_checkout.form.type.checkout', CheckoutType::class)
        ->args([
            param('sylius.model.order.class'),
            param('sylius_live_one_page_checkout.form.type.checkout.validation_groups'),
            service('sylius.repository.address'),
            tagged_iterator('sylius_shop.modifier.address_form_values'),
            service('sylius.comparator.address'),
            service('sylius_live_one_page_checkout.ensurer.order_objects'),
        ])
        ->tag('form.type')
    ;
};
