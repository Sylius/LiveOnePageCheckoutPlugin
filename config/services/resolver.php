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

use Sylius\LiveOnePageCheckoutPlugin\Checkout\ChannelAwareCheckoutResolver;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\inline_service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('sylius_live_one_page_checkout.resolver.checkout', ChannelAwareCheckoutResolver::class)
        ->decorate('sylius.resolver.checkout')
        ->args([
            service('.inner'),
            service('sylius.context.channel'),
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
            inline_service(PathRequestMatcher::class)->args(['%sylius.security.shop_regex%/checkout/(?!one-page(?:/|$)).+']),
            service('router'),
            'sylius_live_one_page_checkout_shop_checkout',
        ])
    ;
};
