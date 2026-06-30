<?php

declare(strict_types=1);

use Sylius\LiveOnePageCheckoutPlugin\Cart\Context\DefaultShippingAddressCartContext;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('sylius_live_one_page_checkout.context.cart.default_shipping_address', DefaultShippingAddressCartContext::class)
        ->decorate('sylius.context.cart.new_shop_based', null, 255)
        ->args([
            service('.inner'),
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
        ])
    ;
};
