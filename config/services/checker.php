<?php

declare(strict_types=1);

use Sylius\LiveOnePageCheckoutPlugin\Checker\OnePageCheckoutEnabledChecker;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OnePageCheckoutEnabledCheckerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_live_one_page_checkout.checker.one_page_checkout_enabled', OnePageCheckoutEnabledChecker::class);

    $services->alias(OnePageCheckoutEnabledCheckerInterface::class, 'sylius_live_one_page_checkout.checker.one_page_checkout_enabled');
};
