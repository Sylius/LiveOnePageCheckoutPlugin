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

use Sylius\Bundle\ApiBundle\Context\UserContextInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\AddressApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CompositeCheckoutApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CouponApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CustomerApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\NotesApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\PaymentMethodApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\ShippingMethodApplier;
use Sylius\LiveOnePageCheckoutPlugin\Api\CommandHandler\CompleteCheckoutHandler;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\CheckoutPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\CheckoutPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\DefaultShippingAddressOrderFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ItemsPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ItemsPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\OrderSummaryFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\OrderSummaryFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentMethodPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentMethodPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentsPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentsPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShipmentsPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShipmentsPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShippingMethodPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\ShippingMethodPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProvider;
use Sylius\LiveOnePageCheckoutPlugin\Api\Provider\OrderItemImageProviderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Serializer\Normalizer\OrderItemImageNormalizer;
use Sylius\LiveOnePageCheckoutPlugin\Api\StateProcessor\PreviewCheckoutProcessor;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\CheckoutCompletionValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\CheckoutEmailNotAllowedValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\CountryCodeExistsValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\ItemAvailabilityValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\OnePageCheckoutNotAvailableValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\OrderNotEmptyValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\ProductEligibilityValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PaymentMethodRequiredValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PromotionCouponEligibilityValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\PromotionCouponExistsValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\ShippingMethodRequiredValidator;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\StandardCheckoutStepBlockedValidator;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();
    $container->parameters()
        ->set('sylius_live_one_page_checkout.api.order_item_image_normalizer.serialization_groups', [
            'sylius:shop:cart:show',
        ])
        ->set('sylius_live_one_page_checkout.api.command_handler.complete_checkout.validation_groups', [
            'sylius',
            'sylius_checkout_complete',
            'sylius_live_one_page_checkout_promotion_coupon_eligibility',
        ])
        ->set('sylius_live_one_page_checkout.api.state_processor.preview_checkout.validation_groups', [
            'sylius_live_one_page_checkout_promotion_coupon_eligibility',
        ])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.customer', CustomerApplier::class)
        ->args([
            service('sylius.resolver.customer'),
        ])
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 50])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.address', AddressApplier::class)
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 40])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.shipping_method', ShippingMethodApplier::class)
        ->args([
            service('sylius.resolver.shipping_methods'),
        ])
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 30])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.payment_method', PaymentMethodApplier::class)
        ->args([
            service('sylius.resolver.payment_methods'),
        ])
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 20])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.coupon', CouponApplier::class)
        ->args([
            service('sylius.repository.promotion_coupon'),
        ])
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 10])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.notes', NotesApplier::class)
        ->tag('sylius_live_one_page_checkout.api.checkout_applier', ['priority' => 0])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.applier.checkout', CompositeCheckoutApplier::class)
        ->args([
            tagged_iterator('sylius_live_one_page_checkout.api.checkout_applier'),
        ])
    ;

    $services->alias(CheckoutApplierInterface::class, 'sylius_live_one_page_checkout.api.applier.checkout');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.order_summary', OrderSummaryFactory::class)
    ;

    $services->alias(OrderSummaryFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.order_summary');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.item_preview', ItemsPreviewFactory::class)
    ;

    $services->alias(ItemsPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.item_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.shipping_method_preview', ShippingMethodPreviewFactory::class)
        ->args([
            service('sylius.registry.shipping_calculator'),
        ])
    ;

    $services->alias(ShippingMethodPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.shipping_method_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.shipment_preview', ShipmentsPreviewFactory::class)
        ->args([
            service('sylius.resolver.shipping_methods'),
            service(ShippingMethodPreviewFactoryInterface::class),
        ])
    ;

    $services->alias(ShipmentsPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.shipment_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.payment_method_preview', PaymentMethodPreviewFactory::class)
    ;

    $services->alias(PaymentMethodPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.payment_method_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.payment_preview', PaymentsPreviewFactory::class)
        ->args([
            service('sylius.resolver.payment_methods'),
            service(PaymentMethodPreviewFactoryInterface::class),
        ])
    ;

    $services->alias(PaymentsPreviewFactoryInterface::class, 'sylius_live_one_page_checkout.api.factory.payment_preview');

    $services
        ->set('sylius_live_one_page_checkout.api.factory.checkout_preview', CheckoutPreviewFactory::class)
        ->args([
            service('sylius_live_one_page_checkout.api.factory.order_summary'),
            service('sylius_live_one_page_checkout.api.factory.item_preview'),
            service('sylius_live_one_page_checkout.api.factory.shipment_preview'),
            service('sylius_live_one_page_checkout.api.factory.payment_preview'),
            service('sylius_live_one_page_checkout.hasher.order'),
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
            service('api_platform.validator'),
            param('sylius_live_one_page_checkout.api.state_processor.preview_checkout.validation_groups'),
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
            service('api_platform.validator'),
            param('sylius_live_one_page_checkout.api.command_handler.complete_checkout.validation_groups'),
        ])
        ->tag('messenger.message_handler', ['bus' => 'sylius.command_bus'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.one_page_checkout_not_available', OnePageCheckoutNotAvailableValidator::class)
        ->args([
            service('sylius.repository.order'),
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_one_page_checkout_not_available'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.promotion_coupon_eligibility', PromotionCouponEligibilityValidator::class)
        ->args([
            service('sylius_api.checker.applied_coupon_eligibility'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_promotion_coupon_eligibility'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.promotion_coupon_exists', PromotionCouponExistsValidator::class)
        ->args([
            service('sylius.repository.promotion_coupon'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_promotion_coupon_exists'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.checkout_email_not_allowed', CheckoutEmailNotAllowedValidator::class)
        ->args([
            service('sylius.repository.order'),
            service(UserContextInterface::class),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_checkout_email_not_allowed'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.order_not_empty', OrderNotEmptyValidator::class)
        ->args([
            service('sylius.repository.order'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_order_not_empty'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.shipping_method_required', ShippingMethodRequiredValidator::class)
        ->args([
            service('sylius.repository.order'),
            service('sylius.repository.shipping_method'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_shipping_method_required'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.payment_method_required', PaymentMethodRequiredValidator::class)
        ->args([
            service('sylius.repository.payment_method'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_payment_method_required'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.checkout_completion', CheckoutCompletionValidator::class)
        ->args([
            service('sylius.repository.order'),
            service('sylius_abstraction.state_machine'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_checkout_completion'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.country_code_exists', CountryCodeExistsValidator::class)
        ->args([
            service('sylius.repository.country'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_country_code_exists'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.product_eligibility', ProductEligibilityValidator::class)
        ->args([
            service('sylius.repository.order'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_product_eligibility'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.item_availability', ItemAvailabilityValidator::class)
        ->args([
            service('sylius.repository.order'),
            service('sylius.checker.inventory.availability'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_item_availability'])
    ;

    $services
        ->set('sylius_live_one_page_checkout.api.validator.standard_checkout_step_blocked', StandardCheckoutStepBlockedValidator::class)
        ->args([
            service('sylius.repository.order'),
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
        ])
        ->tag('validator.constraint_validator', ['alias' => 'sylius_live_one_page_checkout_api_standard_checkout_step_blocked'])
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
            service('sylius.section_resolver.uri_based'),
            service('sylius_live_one_page_checkout.checker.checkout_enabled'),
            service('sylius_live_one_page_checkout.api.provider.order_item_image'),
            '%sylius_live_one_page_checkout.api.order_item_image_normalizer.serialization_groups%',
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
