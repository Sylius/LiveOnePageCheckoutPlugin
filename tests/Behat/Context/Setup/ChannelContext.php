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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Behat\Context\Setup;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Doctrine\Persistence\ObjectManager;
use Sylius\Behat\Service\SharedStorageInterface;
use Sylius\LiveOnePageCheckoutPlugin\Model\ChannelInterface;

final readonly class ChannelContext implements Context
{
    public function __construct(
        private SharedStorageInterface $sharedStorage,
        private ObjectManager $channelManager,
    ) {
    }

    #[Given('one page checkout is enabled for this channel')]
    public function onePageCheckoutIsEnabledForThisChannel(): void
    {
        /** @var ChannelInterface $channel */
        $channel = $this->sharedStorage->get('channel');
        $channel->setOnePageCheckoutEnabled(true);

        $this->channelManager->flush();
    }
}
