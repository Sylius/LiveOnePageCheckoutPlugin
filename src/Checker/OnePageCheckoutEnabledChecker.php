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

use Sylius\LiveOnePageCheckoutPlugin\Model\ChannelInterface;

final class OnePageCheckoutEnabledChecker implements OnePageCheckoutEnabledCheckerInterface
{
    public function isEnabled(ChannelInterface $channel): bool
    {
        return $channel->isOnePageCheckoutEnabled();
    }
}
