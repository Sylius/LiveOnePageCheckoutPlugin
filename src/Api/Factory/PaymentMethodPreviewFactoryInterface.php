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

use Sylius\Component\Payment\Model\PaymentMethodInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\PaymentMethodPreview;

interface PaymentMethodPreviewFactoryInterface
{
    public function create(PaymentMethodInterface $paymentMethod): PaymentMethodPreview;
}
