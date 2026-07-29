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

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class PaymentMethodRequired extends Constraint
{
    public string $message = 'sylius.payment.method.not_blank';

    public function validatedBy(): string
    {
        return 'sylius_live_one_page_checkout_api_payment_method_required';
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
