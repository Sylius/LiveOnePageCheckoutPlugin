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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Applier;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\Component\Payment\Resolver\PaymentMethodsResolverInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final readonly class PaymentMethodApplier implements CheckoutApplierInterface
{
    public function __construct(
        private PaymentMethodsResolverInterface $paymentMethodsResolver,
    ) {
    }

    public function apply(OrderInterface $order, Checkout $command): void
    {
        $payments = array_values($order->getPayments()->toArray());

        foreach ($command->payments as $index => $data) {
            $code = $data['methodCode'] ?? null;
            $payment = $payments[$index] ?? null;

            if (null === $code || '' === $code || !$payment instanceof PaymentInterface) {
                continue;
            }

            $payment->setMethod($this->findSupportedMethod($payment, $code));
        }
    }

    private function findSupportedMethod(PaymentInterface $payment, string $code): ?PaymentMethodInterface
    {
        foreach ($this->paymentMethodsResolver->getSupportedMethods($payment) as $method) {
            if ($method->getCode() === $code) {
                return $method;
            }
        }

        return null;
    }
}
