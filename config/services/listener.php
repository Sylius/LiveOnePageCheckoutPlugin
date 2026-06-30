<?php

declare(strict_types=1);

use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\LiveOnePageCheckoutPlugin\EventListener\OrderIntegrityChecker;
use Sylius\LiveOnePageCheckoutPlugin\EventListener\Workflow\CheckoutCompletionGuard;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('sylius_live_one_page_checkout.listener.order_integrity_checker', OrderIntegrityChecker::class)
        ->decorate('sylius_shop.listener.order_integrity_checker', null, 255)
        ->args([
            service('.inner'),
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
        ])
    ;

    $services
        ->set('sylius_live_one_page_checkout.listener.workflow.checkout_completion_guard', CheckoutCompletionGuard::class)
        ->args([
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
        ])
        ->tag('kernel.event_listener', [
            'event' => 'workflow.' . OrderCheckoutTransitions::GRAPH . '.guard.' . OrderCheckoutTransitions::TRANSITION_COMPLETE,
            'method' => '__invoke',
        ])
    ;
};
