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

namespace Sylius\LiveOnePageCheckoutPlugin\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait ChannelOnePageCheckoutAwareTrait
{
    #[ORM\Column(name: 'one_page_checkout_enabled', type: Types::BOOLEAN, options: ['default' => false])]
    protected bool $onePageCheckoutEnabled = false;

    public function isOnePageCheckoutEnabled(): bool
    {
        return $this->onePageCheckoutEnabled;
    }

    public function setOnePageCheckoutEnabled(bool $onePageCheckoutEnabled): void
    {
        $this->onePageCheckoutEnabled = $onePageCheckoutEnabled;
    }
}
