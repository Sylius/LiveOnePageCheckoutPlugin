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

use Sylius\LiveOnePageCheckoutPlugin\Ensurer\OrderObjectsEnsurer;
use Sylius\LiveOnePageCheckoutPlugin\Ensurer\OrderObjectsEnsurerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('sylius_live_one_page_checkout.ensurer.order_objects', OrderObjectsEnsurer::class)
        ->args([
            service('sylius.factory.address'),
            service('sylius.factory.shipment'),
            service('sylius.factory.payment'),
        ])
    ;

    $services->alias(OrderObjectsEnsurerInterface::class, 'sylius_live_one_page_checkout.ensurer.order_objects');
};
