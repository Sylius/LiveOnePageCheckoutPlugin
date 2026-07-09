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

use Sylius\LiveOnePageCheckoutPlugin\Validator\Constraints\OrderPaymentMethodSelectedValidator;
use Sylius\LiveOnePageCheckoutPlugin\Validator\Constraints\OrderShippingMethodEligibilityValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('sylius.validator.order_shipping_method_eligibility', OrderShippingMethodEligibilityValidator::class)
        ->args([
            service('sylius.checker.shipping_method_eligibility'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_order_shipping_method_eligibility_validator'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.validator.order_payment_method_selected', OrderPaymentMethodSelectedValidator::class)
        ->args([
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_order_payment_method_selected'])
    ;
};
