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
use Sylius\Component\Inventory\Checker\AvailabilityCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class ItemAvailabilityValidator extends ConstraintValidator
{
    /** @param OrderRepositoryInterface<OrderInterface> $orderRepository */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private AvailabilityCheckerInterface $availabilityChecker,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, Checkout::class);

        /** @var ItemAvailability $constraint */
        Assert::isInstanceOf($constraint, ItemAvailability::class);

        $order = $this->orderRepository->findCartByTokenValue($value->orderTokenValue);

        if (!$order instanceof OrderInterface) {
            return;
        }

        foreach ($order->getItems() as $orderItem) {
            $variant = $orderItem->getVariant();
            Assert::notNull($variant);

            if (!$this->availabilityChecker->isStockSufficient($variant, $orderItem->getQuantity())) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->setParameter('%productVariantName%', $variant->getName() ?? $variant->getCode() ?? '')
                    ->addViolation()
                ;
            }
        }
    }
}
