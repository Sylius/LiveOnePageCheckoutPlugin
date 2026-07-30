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
use Sylius\Component\Core\Model\ShippingMethodInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Core\Repository\ShippingMethodRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class ShippingMethodRequiredValidator extends ConstraintValidator
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     * @param ShippingMethodRepositoryInterface<ShippingMethodInterface> $shippingMethodRepository
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private ShippingMethodRepositoryInterface $shippingMethodRepository,
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

        if ([] === $value->shipments) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('shipments')
                ->addViolation()
            ;

            return;
        }

        foreach ($value->shipments as $data) {
            $code = $data['methodCode'] ?? null;

            if (null === $code || '' === trim($code)) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->atPath('shipments')
                    ->addViolation()
                ;

                continue;
            }

            if (null === $this->shippingMethodRepository->findOneBy(['code' => $code])) {
                $this->context
                    ->buildViolation($constraint->notExistMessage)
                    ->atPath('shipments')
                    ->setParameter('%code%', $code)
                    ->addViolation()
                ;
            }
        }
    }
}
