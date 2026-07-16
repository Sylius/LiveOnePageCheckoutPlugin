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

use Sylius\Bundle\CoreBundle\Resolver\CustomerResolverInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;

final readonly class CustomerApplier implements CheckoutApplierInterface
{
    public function __construct(
        private CustomerResolverInterface $customerResolver,
    ) {
    }

    public function apply(OrderInterface $order, Checkout $command): void
    {
        if ($command->email) {
            $order->setCustomer($this->customerResolver->resolve($command->email));
        }
    }
}
