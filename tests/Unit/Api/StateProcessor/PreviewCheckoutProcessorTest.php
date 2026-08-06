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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\StateProcessor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Validator\ValidatorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\CheckoutPreview;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\CheckoutPreviewFactoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\StateProcessor\PreviewCheckoutProcessor;
use Sylius\LiveOnePageCheckoutPlugin\Ensurer\OrderObjectsEnsurerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PreviewCheckoutProcessorTest extends TestCase
{
    /** @var OrderRepositoryInterface<OrderInterface>&MockObject */
    private OrderRepositoryInterface&MockObject $orderRepository;

    private OrderObjectsEnsurerInterface&MockObject $orderObjectsEnsurer;

    private CheckoutApplierInterface&MockObject $checkoutApplier;

    private OrderProcessorInterface&MockObject $orderProcessor;

    private CheckoutPreviewFactoryInterface&MockObject $checkoutPreviewFactory;

    private ValidatorInterface&MockObject $validator;

    private PreviewCheckoutProcessor $processor;

    protected function setUp(): void
    {
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->orderObjectsEnsurer = $this->createMock(OrderObjectsEnsurerInterface::class);
        $this->checkoutApplier = $this->createMock(CheckoutApplierInterface::class);
        $this->orderProcessor = $this->createMock(OrderProcessorInterface::class);
        $this->checkoutPreviewFactory = $this->createMock(CheckoutPreviewFactoryInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->processor = new PreviewCheckoutProcessor(
            $this->orderRepository,
            $this->orderObjectsEnsurer,
            $this->checkoutApplier,
            $this->orderProcessor,
            $this->checkoutPreviewFactory,
            $this->validator,
            ['sylius_live_one_page_checkout_promotion_coupon_eligibility'],
        );
    }

    public function testProcessesCheckoutAndReturnsPreview(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token123');
        $preview = $this->createMock(CheckoutPreview::class);
        $operation = $this->createMock(Operation::class);

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('token123')
            ->willReturn($order);

        $this->orderObjectsEnsurer->expects(self::once())
            ->method('ensure')
            ->with($order);

        $this->checkoutApplier->expects(self::once())
            ->method('apply')
            ->with($order, $command);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($order, ['groups' => ['sylius_live_one_page_checkout_promotion_coupon_eligibility']]);

        $this->orderProcessor->expects(self::once())
            ->method('process')
            ->with($order);

        $this->checkoutPreviewFactory->expects(self::once())
            ->method('create')
            ->with($order)
            ->willReturn($preview);

        $result = $this->processor->process($command, $operation);

        self::assertSame($preview, $result);
    }

    public function testThrowsNotFoundExceptionWhenCartNotFound(): void
    {
        $command = new Checkout(orderTokenValue: 'nonexistent');
        $operation = $this->createMock(Operation::class);

        $this->orderRepository->expects(self::once())
            ->method('findCartByTokenValue')
            ->with('nonexistent')
            ->willReturn(null);

        $this->orderObjectsEnsurer->expects(self::never())->method('ensure');
        $this->checkoutApplier->expects(self::never())->method('apply');
        $this->validator->expects(self::never())->method('validate');
        $this->orderProcessor->expects(self::never())->method('process');
        $this->checkoutPreviewFactory->expects(self::never())->method('create');

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Cart has not been found.');

        $this->processor->process($command, $operation);
    }

    public function testProcessingChainIsFollowedInCorrectOrder(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token123');
        $preview = $this->createMock(CheckoutPreview::class);
        $operation = $this->createMock(Operation::class);

        $callOrder = [];

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);

        $this->orderObjectsEnsurer->method('ensure')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'ensure';
            });

        $this->checkoutApplier->method('apply')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'apply';
            });

        $this->validator->method('validate')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'validate';
            });

        $this->orderProcessor->method('process')
            ->willReturnCallback(function () use (&$callOrder) {
                $callOrder[] = 'process';
            });

        $this->checkoutPreviewFactory->method('create')->willReturn($preview);

        $this->processor->process($command, $operation);

        self::assertSame(['ensure', 'apply', 'validate', 'process'], $callOrder);
    }

    public function testAcceptsOptionalUriVariablesAndContext(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token123');
        $preview = $this->createMock(CheckoutPreview::class);
        $operation = $this->createMock(Operation::class);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->orderObjectsEnsurer->method('ensure');
        $this->checkoutApplier->method('apply');
        $this->orderProcessor->method('process');
        $this->checkoutPreviewFactory->method('create')->willReturn($preview);

        $uriVariables = ['id' => '123'];
        $context = ['some' => 'context'];

        $result = $this->processor->process($command, $operation, $uriVariables, $context);

        self::assertSame($preview, $result);
    }

    public function testProcessesCheckoutWithDifferentOperations(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $command = new Checkout(orderTokenValue: 'token456');
        $preview = $this->createMock(CheckoutPreview::class);
        $operation = $this->createMock(Operation::class);

        $this->orderRepository->method('findCartByTokenValue')->willReturn($order);
        $this->orderObjectsEnsurer->method('ensure');
        $this->checkoutApplier->method('apply');
        $this->orderProcessor->method('process');
        $this->checkoutPreviewFactory->method('create')->willReturn($preview);

        $result = $this->processor->process($command, $operation);

        self::assertInstanceOf(CheckoutPreview::class, $result);
    }
}
