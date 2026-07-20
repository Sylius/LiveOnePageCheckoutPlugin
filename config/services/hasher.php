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

use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledChecker;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OrderConsistencyChecker;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OrderConsistencyCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasher;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasherInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('sylius_live_one_page_checkout.hasher.order', OrderHasher::class)
        ->args([
            service('sylius_live_one_page_checkout.provider.order_hash_data'),
        ])
    ;

    $services->alias(OrderHasherInterface::class, 'sylius_live_one_page_checkout.hasher.order');
};
