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

use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\AddressApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CompositeCheckoutApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CouponApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CustomerApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\PaymentMethodApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\ShippingMethodApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\CommandHandler\CompleteCheckoutHandler;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\CheckoutPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\AddressPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\AddressPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\CheckoutPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\CheckoutPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\DefaultShippingAddressOrderFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ItemPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ItemPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\OrderSummaryFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\OrderSummaryFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShipmentPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShipmentPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShippingMethodPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShippingMethodPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProvider;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProviderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Security\Voter\CheckoutAvailabilityVoter;
use Sylius\LiveOnePageCheckoutPlugin\Api\Serializer\Normalizer\OrderItemImageNormalizer;
use Sylius\LiveOnePageCheckoutPlugin\Api\StateProcessor\PreviewCheckoutProcessor;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PromotionCouponEligibilityValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $container->parameters()
        ->set('sylius_live_one_page_checkout.api.checkout_preview.class', CheckoutPreview::class)
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.customer', CustomerApplier::class)
        ->args([
            service('sylius.resolver.customer'),
        ])
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 40])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.address', AddressApplier::class)
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 30])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.shipping_method', ShippingMethodApplier::class)
        ->args([
            service('sylius.resolver.shipping_methods'),
        ])
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 20])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.payment_method', PaymentMethodApplier::class)
        ->args([
            service('sylius.resolver.payment_methods'),
        ])
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 10])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.coupon', CouponApplier::class)
        ->args([
            service('sylius.repository.promotion_coupon'),
        ])
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 0])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.checkout', CompositeCheckoutApplier::class)
        ->args([
            tagged_iterator('sylius_live_one_page_checkout.api.checkout_applier'),
        ])
    ;

    $services->alias(CheckoutApplierInterface::class, 'sylius_live_one_page_checkout.api.applier.checkout');

    $services->set('sylius_live_one_page_checkout.api.factory.address_preview', AddressPreviewFactory::class);

    $services->alias(AddressPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.address_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.order_summary', OrderSummaryFactory::class)
    ;

    $services->alias(OrderSummaryFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.order_summary');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.item_preview', ItemPreviewFactory::class)
    ;

    $services->alias(ItemPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.item_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.shipping_method_preview', ShippingMethodPreviewFactory::class)
        ->args([
            service('sylius.registry.shipping_calculator'),
        ])
    ;

    $services->alias(ShippingMethodPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.shipping_method_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.shipment_preview', ShipmentPreviewFactory::class)
        ->args([
            service('sylius.resolver.shipping_methods'),
            service(ShippingMethodPreviewFactoryInterface::class),
        ])
    ;

    $services->alias(ShipmentPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.shipment_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.payment_preview', PaymentPreviewFactory::class)
        ->args([
            service('sylius.resolver.payment_methods'),
        ])
    ;

    $services->alias(PaymentPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.payment_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.checkout_preview', CheckoutPreviewFactory::class)
        ->args([
            service('sylius_live_one_page_checkout.api.factory.order_summary'),
            service('sylius_live_one_page_checkout.api.factory.item_preview'),
            service('sylius_live_one_page_checkout.api.factory.shipment_preview'),
            service('sylius_live_one_page_checkout.api.factory.payment_preview'),
            service('sylius_live_one_page_checkout.hasher.order'),
            service('sylius_live_one_page_checkout.api.factory.address_preview'),
        ])
    ;

    $services->alias(CheckoutPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.checkout_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.state_processor.preview_checkout', PreviewCheckoutProcessor::class)
        ->args([
            service('sylius.repository.order'),
            service('sylius_live_one_page_checkout.ensurer.order_objects'),
            service('sylius_live_one_page_checkout.api.applier.checkout'),
            service('sylius_live_one_page_checkout.order_processing.order_processor.composite'),
            service('sylius_live_one_page_checkout.api.factory.checkout_preview'),
        ])
        ->tag('api_platform.state_processor')
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.command_handler.complete_checkout', CompleteCheckoutHandler::class)
        ->args([
            service('sylius.repository.order'),
            service('sylius_live_one_page_checkout.ensurer.order_objects'),
            service('sylius_live_one_page_checkout.api.applier.checkout'),
            service('sylius.order_processing.order_processor'),
            service('sylius_live_one_page_checkout.checker.order_consistency'),
            service('sylius_abstraction.state_machine'),
            service('sylius.event_bus'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.command_bus'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.security.voter.checkout_availability', CheckoutAvailabilityVoter::class)
        ->args([
            service('sylius.repository.order'),
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
        ])
        ->tag('security.voter')
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.promotion_coupon_eligibility', PromotionCouponEligibilityValidator::class)
        ->args([
            service('sylius.repository.promotion_coupon'),
            service('sylius.repository.order'),
            service('sylius_api.checker.applied_coupon_eligibility'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_promotion_coupon_eligibility'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.provider.order_item_image', OrderItemImageProvider::class)
    ;

    $services->alias(OrderItemImageProviderInterface::class, 'sylius_live_one_page_checkout.api.provider.order_item_image');

    $services
        ->set('sylius_live_one_page_checkout.api.normalizer.order_item_image', OrderItemImageNormalizer::class)
        ->args([
            service('liip_imagine.cache.manager'),
            service('request_stack'),
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
            service('sylius_live_one_page_checkout.api.provider.order_item_image'),
            'sylius_shop_product_small_thumbnail',
        ])
        ->tag('serializer.normalizer', ['priority' => 64])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.factory.order.default_shipping_address', DefaultShippingAddressOrderFactory::class)
        ->decorate('sylius.custom_factory.order')
        ->args([
            service('.inner'),
            service('sylius_live_one_page_checkout.applier.default_shipping_address'),
        ])
    ;
};
