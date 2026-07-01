<?php

declare(strict_types=1);

use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledChecker;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OrderConsistencyChecker;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OrderConsistencyCheckerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_live_one_page_checkout.checker.checkout_enabled', CheckoutEnabledChecker::class);

    $services->alias(CheckoutEnabledCheckerInterface::class, 'sylius_live_one_page_checkout.checker.checkout_enabled');

    $services
        ->set('sylius_live_one_page_checkout.checker.order_consistency', OrderConsistencyChecker::class)
        ->args([
            service('sylius_live_one_page_checkout.hasher.order'),
        ])
    ;

    $services->alias(OrderConsistencyCheckerInterface::class, 'sylius_live_one_page_checkout.checker.order_consistency');
};
