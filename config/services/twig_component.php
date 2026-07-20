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

use Sylius\LiveOnePageCheckoutPlugin\Form\Type\CheckoutType;
use Sylius\LiveOnePageCheckoutPlugin\Twig\Component\Checkout\FormComponent;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('sylius_live_one_page_checkout.twig.component.checkout.form', FormComponent::class)
        ->args([
            service('sylius.repository.order'),
            service('form.factory'),
            param('sylius.model.order.class'),
            CheckoutType::class,
            service('sylius.context.customer'),
            service('sylius_live_one_page_checkout.order_processing.order_processor.composite'),
            service('sylius_live_one_page_checkout.hasher.order'),
        ])
        ->tag('sylius.live_component.shop', ['key' => 'sylius_live_one_page_checkout:checkout:form'])
    ;
};
