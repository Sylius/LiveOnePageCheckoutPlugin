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

use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PromotionCouponEligibilityValidator;
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
        ->set('sylius_live_one_page_checkout.validator.promotion_coupon_eligibility', PromotionCouponEligibilityValidator::class)
        ->args([
            service('sylius.repository.promotion_coupon'),
            service('sylius.repository.order'),
            service('sylius_api.checker.applied_coupon_eligibility'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_promotion_coupon_eligibility'])
    ;
};
