<?php

declare(strict_types=1);

use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledChecker;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_live_one_page_checkout.checker.checkout_enabled', CheckoutEnabledChecker::class);

    $services->alias(CheckoutEnabledCheckerInterface::class, 'sylius_live_one_page_checkout.checker.checkout_enabled');
};
