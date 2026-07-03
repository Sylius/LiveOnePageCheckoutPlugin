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

namespace Sylius\LiveOnePageCheckoutPlugin\Checker;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\LiveOnePageCheckoutPlugin\Hasher\OrderHasherInterface;

final readonly class OrderConsistencyChecker implements OrderConsistencyCheckerInterface
{
    public function __construct(
        private OrderHasherInterface $hasher,
    ) {
    }

    public function isConsistent(OrderInterface $order, string $submittedHash): bool
    {
        return $this->hasher->hash($order) === $submittedHash;
    }
}
