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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\CheckoutPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\CheckoutPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Ensurer\OrderObjectsEnsurerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Webmozart\Assert\Assert;

/**
 * @implements ProcessorInterface<Checkout, CheckoutPreview>
 */
final readonly class PreviewCheckoutProcessor implements ProcessorInterface
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     * @param list<string> $validationGroups
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderObjectsEnsurerInterface $orderObjectsEnsurer,
        private CheckoutApplierInterface $checkoutApplier,
        private OrderProcessorInterface $orderProcessor,
        private CheckoutPreviewFactoryInterface $checkoutPreviewFactory,
        private ValidatorInterface $validator,
        private array $validationGroups,
    ) {
    }

    /**
     * @param Checkout $data
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): CheckoutPreview
    {
        Assert::isInstanceOf($data, Checkout::class);

        $order = $this->orderRepository->findCartByTokenValue($data->orderTokenValue);

        if (!$order instanceof OrderInterface) {
            throw new NotFoundHttpException('Cart has not been found.');
        }

        $this->orderObjectsEnsurer->ensure($order);
        $this->checkoutApplier->apply($order, $data);

        $this->validator->validate($order, ['groups' => $this->validationGroups]);

        $this->orderProcessor->process($order);

        return $this->checkoutPreviewFactory->create($order);
    }
}
