<?php

declare(strict_types=1);

use Sylius\LiveOnePageCheckoutPlugin\WinzouStateMachine\CheckoutCompletionGuard;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('sylius_live_one_page_checkout.winzou.guard.checkout_completion', CheckoutCompletionGuard::class)
        ->args([
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
        ])
        ->public()
    ;
};
