<?php

declare(strict_types=1);

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Channel as BaseChannel;
use Sylius\LiveOnePageCheckoutPlugin\Model\ChannelInterface;
use Sylius\LiveOnePageCheckoutPlugin\Model\ChannelOnePageCheckoutAwareTrait;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_channel')]
class Channel extends BaseChannel implements ChannelInterface
{
    use ChannelOnePageCheckoutAwareTrait;
}
