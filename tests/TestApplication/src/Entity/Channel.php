<?php

declare(strict_types=1);

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Entity;

use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Core\Model\Channel as BaseChannel;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareTrait;

#[ORM\Entity]
#[ORM\Table(name: 'sylius_channel')]
class Channel extends BaseChannel implements ChannelOnePageCheckoutAwareInterface
{
    use ChannelOnePageCheckoutAwareTrait;
}
