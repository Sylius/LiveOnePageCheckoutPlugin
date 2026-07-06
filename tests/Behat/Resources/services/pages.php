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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Behat\Page\Shop\Checkout\OnePageCheckoutPage;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $services->defaults()->public();

    $services->set('sylius_live_one_page_checkout.behat.page.shop.checkout.one_page', OnePageCheckoutPage::class)
        ->args([
            service('behat.mink.default_session'),
            service('behat.mink.parameters'),
            service('router'),
        ]);
};
