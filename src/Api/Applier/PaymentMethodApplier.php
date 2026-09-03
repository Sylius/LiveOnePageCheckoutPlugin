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
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\Component\Payment\Model\PaymentInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final readonly class PaymentMethodApplier implements CheckoutApplierInterface
{
    /** @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository */
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
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

            $method = $this->paymentMethodRepository->findOneBy(['code' => $code]);

            $payment->setMethod($method instanceof PaymentMethodInterface ? $method : null);
        }
    }
}
