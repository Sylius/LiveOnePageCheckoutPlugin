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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Factory;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\PaymentPreview;

final readonly class PaymentPreviewFactory implements PaymentPreviewFactoryInterface
{
    public function __construct(
        private PaymentMethodsResolverInterface $paymentMethodsResolver,
    ) {
    }

    public function create(OrderInterface $order): array
    {
        $previews = [];

        foreach ($order->getPayments() as $payment) {
            $previews[] = new PaymentPreview(
                $payment->getMethod()?->getCode(),
                array_values($this->paymentMethodsResolver->getSupportedMethods($payment)),
            );
        }

        return $previews;
    }
}
