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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Command;

use Sylius\Bundle\ApiBundle\Attribute\LoggedInCustomerEmailAware;
use Sylius\Bundle\ApiBundle\Attribute\OrderTokenValueAware;
use Sylius\Component\Addressing\Model\AddressInterface;

#[OrderTokenValueAware]
#[LoggedInCustomerEmailAware]
final class Checkout
{
    /**
     * @param array<int, array{methodCode?: string|null}> $shipments
     * @param array<int, array{methodCode?: string|null}> $payments
     */
    public function __construct(
        public readonly string $orderTokenValue,
        public readonly ?string $email = null,
        public readonly ?AddressInterface $billingAddress = null,
        public readonly ?AddressInterface $shippingAddress = null,
        public readonly array $shipments = [],
        public readonly array $payments = [],
        public readonly ?string $couponCode = null,
        public readonly ?string $hash = null,
        public readonly ?string $notes = null,
    ) {
    }
}
