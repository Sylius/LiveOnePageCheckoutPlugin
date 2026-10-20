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

use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Repository\PaymentMethodRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class PaymentMethodRequiredValidator extends ConstraintValidator
{
    /** @param PaymentMethodRepositoryInterface<PaymentMethodInterface> $paymentMethodRepository */
    public function __construct(
        private PaymentMethodRepositoryInterface $paymentMethodRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, Checkout::class);

        Assert::isInstanceOf($constraint, PaymentMethodRequired::class);

        if ([] === $value->payments) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath('payments')
                ->addViolation()
            ;

            return;
        }

        foreach ($value->payments as $data) {
            $code = $data['methodCode'] ?? null;

            if (null === $code || '' === trim($code)) {
                $this->context
                    ->buildViolation($constraint->message)
                    ->atPath('payments')
                    ->addViolation()
                ;

                continue;
            }

            if (null === $this->paymentMethodRepository->findOneBy(['code' => $code])) {
                $this->context
                    ->buildViolation($constraint->notExistMessage)
                    ->atPath('payments')
                    ->setParameter('%code%', $code)
                    ->addViolation()
                ;
            }
        }
    }
}
