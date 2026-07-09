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

namespace Sylius\LiveOnePageCheckoutPlugin\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class OrderPaymentMethodSelected extends Constraint
{
    public string $message = 'sylius_live_one_page_checkout.checkout.payment_method_required';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return 'sylius_live_one_page_checkout_order_payment_method_selected';
    }
}
