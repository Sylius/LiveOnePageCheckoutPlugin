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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Behat\Context\Ui\Admin;

use Behat\Behat\Context\Context;
use Behat\Step\Then;
use Behat\Step\When;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Behat\Element\Admin\Channel\OnePageCheckoutFormElementInterface;
use Webmozart\Assert\Assert;

final readonly class ManagingChannelsContext implements Context
{
    public function __construct(
        private OnePageCheckoutFormElementInterface $onePageCheckoutFormElement,
    ) {
    }

    #[When('I enable one page checkout')]
    public function iEnableOnePageCheckout(): void
    {
        $this->onePageCheckoutFormElement->enableOnePageCheckout();
    }

    #[When('I disable one page checkout')]
    public function iDisableOnePageCheckout(): void
    {
        $this->onePageCheckoutFormElement->disableOnePageCheckout();
    }

    #[Then('one page checkout should be enabled for this channel')]
    public function onePageCheckoutShouldBeEnabledForThisChannel(): void
    {
        Assert::true($this->onePageCheckoutFormElement->isOnePageCheckoutEnabled());
    }

    #[Then('one page checkout should be disabled for this channel')]
    public function onePageCheckoutShouldBeDisabledForThisChannel(): void
    {
        Assert::false($this->onePageCheckoutFormElement->isOnePageCheckoutEnabled());
    }
}
