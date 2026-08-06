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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Factory;

use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentMethodPreviewFactory;
use Sylius\LiveOnePageCheckoutPlugin\Api\Factory\PaymentMethodPreviewFactoryInterface;

final class PaymentMethodPreviewFactoryTest extends TestCase
{
    private PaymentMethodPreviewFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new PaymentMethodPreviewFactory();
    }

    public function testImplementsPaymentMethodPreviewFactoryInterface(): void
    {
        self::assertInstanceOf(PaymentMethodPreviewFactoryInterface::class, $this->factory);
    }

    public function testCreatesPaymentMethodPreview(): void
    {
        $paymentMethod = $this->createMock(PaymentMethodInterface::class);
        $paymentMethod->method('getCode')->willReturn('CASH_ON_DELIVERY');
        $paymentMethod->method('getName')->willReturn('Cash on delivery');
        $paymentMethod->method('getDescription')->willReturn('Pay on delivery');

        $result = $this->factory->create($paymentMethod);

        self::assertSame('CASH_ON_DELIVERY', $result->code);
        self::assertSame('Cash on delivery', $result->name);
        self::assertSame('Pay on delivery', $result->description);
    }
}
