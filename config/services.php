<?php

declare(strict_types=1);

use Sylius\Component\Order\Processor\CompositeOrderProcessor;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $container->import('services/**');
    $services = $container->services();

    $services
        ->set('sylius_live_one_page_checkout.order_processing.order_processor.composite', CompositeOrderProcessor::class)
    ;
};
