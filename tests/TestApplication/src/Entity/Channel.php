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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Channel as BaseChannel;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareTrait;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_channel')]
class Channel extends BaseChannel implements ChannelInterface
{
    use ChannelOnePageCheckoutAwareTrait;
}
