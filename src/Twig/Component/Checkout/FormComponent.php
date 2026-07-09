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

namespace Sylius\LiveOnePageCheckoutPlugin\Twig\Component\Checkout;

use Sylius\Bundle\UiBundle\Twig\Component\ResourceFormComponentTrait;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasherInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
class FormComponent
{
    /** @use ResourceFormComponentTrait<OrderInterface> */
    use ResourceFormComponentTrait;

    use TemplatePropTrait;

    #[ExposeInTemplate]
    public ?string $stateHash = null;

    /**
     * @param OrderRepositoryInterface<OrderInterface> $repository
     */
    public function __construct(
        OrderRepositoryInterface $repository,
        FormFactoryInterface $formFactory,
        string $resourceClass,
        string $formClass,
        private readonly CustomerContextInterface $customerContext,
        private readonly OrderProcessorInterface $orderProcessor,
        private readonly OrderHasherInterface $orderHasher,
    ) {
        $this->initialize($repository, $formFactory, $resourceClass, $formClass);
    }

    #[PostMount]
    #[PreReRender(priority: -100)]
    public function setHash(): void
    {
        $order = $this->resource;

        if (null === $order) {
            return;
        }

        $this->orderProcessor->process($order);
        $this->stateHash = $this->orderHasher->hash($order);
    }

    protected function instantiateForm(): FormInterface
    {
        /** @var OrderInterface $order */
        $order = $this->resource;

        return $this->formFactory->create(
            $this->formClass,
            $order,
            ['customer' => $this->customerContext->getCustomer()],
        );
    }

    #[LiveAction]
    public function removeCoupon(): void
    {
        $this->formValues['promotionCoupon'] = '';

        $this->submitForm(false);
    }

    #[LiveAction]
    public function toggleNewShippingAddress(): void
    {
        $this->clearAddressForm('newShippingAddress', 'defaultShippingAddress', 'shippingAddress');
    }

    #[LiveAction]
    public function toggleNewBillingAddress(): void
    {
        $this->clearAddressForm('newBillingAddress', 'defaultBillingAddress', 'billingAddress');
    }

    #[LiveAction]
    public function toggleDifferentBillingAddress(): void
    {
        $this->clearAddressForm('differentBillingAddress', 'defaultBillingAddress', 'billingAddress');

        if (array_key_exists('newBillingAddress', $this->formValues)) {
            $this->formValues['newBillingAddress'] = false;
        }
    }

    private function clearAddressForm(string $flagKey, string $defaultKey, string $addressKey): void
    {
        if (!(bool) ($this->formValues[$flagKey] ?? false)) {
            return;
        }

        if (array_key_exists($defaultKey, $this->formValues)) {
            $this->formValues[$defaultKey] = '';
        }

        $this->formValues[$addressKey] = [];
    }
}
