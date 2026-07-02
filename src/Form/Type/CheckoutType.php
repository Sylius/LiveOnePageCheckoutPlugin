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

namespace Sylius\LiveOnePageCheckoutPlugin\Form\Type;

use Sylius\Bundle\CoreBundle\Form\Type\Checkout\ChangePaymentMethodType;
use Sylius\Bundle\CoreBundle\Form\Type\Customer\CustomerCheckoutGuestType;
use Sylius\Bundle\PromotionBundle\Form\Type\PromotionCouponToCodeType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Sylius\Bundle\ShopBundle\Form\Type\AddressType;
use Sylius\Bundle\ShopBundle\Modifier\AddressFormValuesModifierInterface;
use Sylius\Component\Addressing\Comparator\AddressComparatorInterface;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\AddressRepositoryInterface;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

final class CheckoutType extends AbstractResourceType
{
    public const NAME = 'sylius_live_one_page_checkout_checkout';

    /**
     * @param string[] $validationGroups
     * @param iterable<AddressFormValuesModifierInterface> $addressFormValuesModifiers
     */
    public function __construct(
        string $dataClass,
        array $validationGroups,
        protected readonly AddressRepositoryInterface $addressRepository,
        protected readonly iterable $addressFormValuesModifiers,
        protected readonly AddressComparatorInterface $addressComparator,
    ) {
        parent::__construct($dataClass, $validationGroups);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stateHash', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('promotionCoupon', PromotionCouponToCodeType::class, [
                'by_reference' => false,
                'label' => 'sylius.form.cart.coupon',
                'required' => false,
            ])
            ->addEventListener(FormEvents::PRE_SET_DATA, fn (FormEvent $event) => $this->buildBaseFields($event))
            ->addEventListener(FormEvents::PRE_SET_DATA, fn (FormEvent $event) => $this->buildCustomerAndAddressChoices($event, $options))
            ->addEventListener(FormEvents::PRE_SUBMIT, fn (FormEvent $event) => $this->applySelectedDefaultAddress($event, $options, 'shipping'))
            ->addEventListener(FormEvents::PRE_SUBMIT, fn (FormEvent $event) => $this->applySelectedDefaultAddress($event, $options, 'billing'))
            ->addEventListener(FormEvents::PRE_SUBMIT, fn (FormEvent $event) => $this->synchronizeAddresses($event, $options))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'customer' => null,
            ])
            ->setNormalizer('validation_groups', fn (Options $options, array $validationGroups) => function (FormInterface $form) use ($validationGroups) {
                if ((bool) $form->get('promotionCoupon')->getNormData()) { // Validate the coupon if it was sent
                    $validationGroups[] = 'sylius_promotion_coupon';
                }

                return $validationGroups;
            })
        ;
    }

    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    private function buildBaseFields(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var OrderInterface $order */
        $order = $event->getData();
        $channel = $order->getChannel();

        $form
            ->add('shippingAddress', AddressType::class, [
                'shippable' => true,
                'channel' => $channel,
            ])
            ->add('billingAddress', AddressType::class, [
                'channel' => $channel,
            ])
            ->add('payments', ChangePaymentMethodType::class, [
                'entry_type' => PaymentType::class,
                'label' => false,
            ])
        ;

        if ($order->isShippingRequired()) {
            $form
                ->add('differentBillingAddress', CheckboxType::class, [
                    'mapped' => false,
                    'required' => false,
                    'data' => $this->hasDifferentBillingAddress($order),
                    'label' => 'sylius.form.checkout.addressing.different_billing_address',
                ])
                ->add('shipments', CollectionType::class, [
                    'entry_type' => ShipmentType::class,
                    'label' => false,
                ])
            ;
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    private function buildCustomerAndAddressChoices(FormEvent $event, array $options): void
    {
        $form = $event->getForm();
        /** @var OrderInterface $order */
        $order = $event->getData();
        /** @var CustomerInterface $customer */
        $customer = $options['customer'];

        /** @var CustomerInterface|null $resourceCustomer */
        $resourceCustomer = $order->getCustomer();

        if ($this->shouldCollectGuestCustomer($resourceCustomer, $customer)) {
            $form->add('customer', CustomerCheckoutGuestType::class, ['constraints' => [new Valid()]]);
        } else {
            if ($order->isShippingRequired()) {
                $this->addAddressChoice($form, $customer, $order->getShippingAddress(), 'shipping');
            }

            $this->addAddressChoice($form, $customer, $order->getBillingAddress(), 'billing');
        }
    }

    private function shouldCollectGuestCustomer(?CustomerInterface $resourceCustomer, ?CustomerInterface $customer): bool
    {
        return (null === $customer && null === $resourceCustomer) ||
            (null !== $resourceCustomer && null === $resourceCustomer->getUser()) ||
            ($resourceCustomer !== $customer);
    }

    private function hasDifferentBillingAddress(OrderInterface $order): bool
    {
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        return null !== $billingAddress &&
            null !== $shippingAddress &&
            !$this->addressComparator->equal($billingAddress, $shippingAddress);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function applySelectedDefaultAddress(FormEvent $event, array $options, string $scope): void
    {
        /** @var array<string, mixed> $orderData */
        $orderData = $event->getData();
        /** @var CustomerInterface|null $customer */
        $customer = $options['customer'];

        $defaultField = 'default' . ucfirst($scope) . 'Address';
        $newField = 'new' . ucfirst($scope) . 'Address';
        $addressField = $scope . 'Address';

        $selectedDefaultAddressId = $orderData[$defaultField] ?? null;

        if (null !== $customer && is_string($selectedDefaultAddressId) && '' !== $selectedDefaultAddressId) {
            $orderData[$newField] = false;

            $address = $this->addressRepository->findOneByCustomer($selectedDefaultAddressId, $customer);

            if (null !== $address) {
                $orderData[$addressField] = $this->buildAddressFormValues($address);
            }
        }

        $event->setData($orderData);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function synchronizeAddresses(FormEvent $event, array $options): void
    {
        /** @var array<string, mixed> $orderData */
        $orderData = $event->getData();
        /** @var OrderInterface $order */
        $order = $options['data'];

        if (!$order->isShippingRequired()) {
            if (isset($orderData['billingAddress'])) {
                $orderData['shippingAddress'] = $orderData['billingAddress'];
            }

            $event->setData($orderData);

            return;
        }

        $differentBillingAddress = $orderData['differentBillingAddress'] ?? false;

        if (!$differentBillingAddress && isset($orderData['shippingAddress'])) {
            $orderData['billingAddress'] = $orderData['shippingAddress'];
        }

        $event->setData($orderData);
    }

    private function addAddressChoice(FormInterface $form, CustomerInterface $customer, ?AddressInterface $orderAddress, string $scope): void
    {
        $default = $this->findMatchingCustomerAddress($customer, $orderAddress);

        $form
            ->add('default' . ucfirst($scope) . 'Address', DefaultAddressChoiceType::class, [
                'customer' => $customer,
                'data' => $default,
                'constraints' => [$this->buildAddressRequiredCallback($scope)],
            ])
            ->add('new' . ucfirst($scope) . 'Address', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'data' => null !== $orderAddress && null === $default,
                'label' => 'sylius_live_one_page_checkout.form.checkout.new_' . $scope . '_address',
            ])
        ;
    }

    private function buildAddressRequiredCallback(string $scope): Callback
    {
        $newField = 'new' . ucfirst($scope) . 'Address';
        $message = 'sylius_live_one_page_checkout.checkout.' . $scope . '_address_required';

        return new Callback(
            callback: function (?AddressInterface $value, ExecutionContextInterface $context) use ($scope, $newField, $message): void {
                /** @var FormInterface $root */
                $root = $context->getRoot();

                if ('billing' === $scope && $root->has('differentBillingAddress') && !(bool) $root->get('differentBillingAddress')->getData()) {
                    return;
                }

                $newChecked = (bool) $root->get($newField)->getData();

                if (null !== $value || $newChecked) {
                    return;
                }

                $context
                    ->buildViolation($message)
                    ->addViolation()
                ;
            },
            groups: ['sylius'],
        );
    }

    private function findMatchingCustomerAddress(CustomerInterface $customer, ?AddressInterface $orderAddress): ?AddressInterface
    {
        if (null === $orderAddress) {
            return null;
        }

        foreach ($customer->getAddresses() as $candidate) {
            if ($this->addressComparator->equal($orderAddress, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAddressFormValues(AddressInterface $address): array
    {
        $values = [];
        foreach ($this->addressFormValuesModifiers as $modifier) {
            $values = $modifier->modify($values, $address);
        }

        return $values;
    }
}
