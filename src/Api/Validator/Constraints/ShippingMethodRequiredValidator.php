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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class ShippingMethodRequiredValidator extends ConstraintValidator
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, Checkout::class);

        /** @var ShippingMethodRequired $constraint */
        Assert::isInstanceOf($constraint, ShippingMethodRequired::class);

        $order = $this->orderRepository->findCartByTokenValue($value->orderTokenValue);

        if (!$order instanceof OrderInterface || !$order->isShippingRequired()) {
            return;
        }

        for ($index = 0; $index < $order->getShipments()->count(); ++$index) {
            $code = $value->shipments[$index]['methodCode'] ?? null;

            if (null === $code || '' === trim($code)) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->atPath('shipments')
                    ->addViolation()
                ;
            }
        }
    }
}
