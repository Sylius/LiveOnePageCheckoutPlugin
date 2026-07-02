<?php

declare(strict_types=1);

namespace Sylius\LiveOnePageCheckoutPlugin\Twig\Component\Checkout;

use Sylius\Bundle\UiBundle\Twig\Component\ResourceFormComponentTrait;
use Sylius\Bundle\UiBundle\Twig\Component\TemplatePropTrait;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Customer\Context\CustomerContextInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasherInterface;
use Sylius\Resource\Factory\FactoryInterface;
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
     * @param FactoryInterface<AddressInterface> $addressFactory
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
        $this->orderProcessor->process($this->resource);
        $this->stateHash = $this->orderHasher->hash($this->resource);
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(
            $this->formClass,
            $this->resource,
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
        $this->formValues['newBillingAddress'] = false;
    }

    private function clearAddressForm(string $flagKey, string $defaultKey, string $addressKey): void
    {
        if (!(bool) ($this->formValues[$flagKey] ?? false)) {
            return;
        }

        $this->formValues[$defaultKey] = '';

        if (is_array($this->formValues[$addressKey] ?? null)) {
            foreach (array_keys($this->formValues[$addressKey]) as $key) {
                $this->formValues[$addressKey][$key] = '';
            }
        }
    }
}
