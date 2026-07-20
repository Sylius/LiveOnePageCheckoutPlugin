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

use Sylius\LiveOnePageCheckoutPlugin\Provider\OrderHashDataProvider;
use Sylius\LiveOnePageCheckoutPlugin\Provider\OrderHashDataProviderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Provider\SubmittedHashProvider;
use Sylius\LiveOnePageCheckoutPlugin\Provider\SubmittedHashProviderInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('sylius_live_one_page_checkout.provider.order_hash_data', OrderHashDataProvider::class);

    $services->alias(OrderHashDataProviderInterface::class, 'sylius_live_one_page_checkout.provider.order_hash_data');

    $services
        ->set('sylius_live_one_page_checkout.provider.submitted_hash', SubmittedHashProvider::class)
        ->args([
            service('request_stack'),
        ])
    ;

    $services->alias(SubmittedHashProviderInterface::class, 'sylius_live_one_page_checkout.provider.submitted_hash');
};
