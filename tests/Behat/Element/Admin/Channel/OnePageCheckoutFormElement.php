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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Behat\Element\Admin\Channel;

use Sylius\Behat\Element\Admin\Crud\FormElement as BaseFormElement;

class OnePageCheckoutFormElement extends BaseFormElement implements OnePageCheckoutFormElementInterface
{
    public function enableOnePageCheckout(): void
    {
        $this->getElement('one_page_checkout_enabled')->check();
    }

    public function disableOnePageCheckout(): void
    {
        $this->getElement('one_page_checkout_enabled')->uncheck();
    }

    public function isOnePageCheckoutEnabled(): bool
    {
        return $this->getElement('one_page_checkout_enabled')->isChecked();
    }

    protected function getDefinedElements(): array
    {
        return array_merge(parent::getDefinedElements(), [
            'one_page_checkout_enabled' => '[data-test-one-page-checkout-enabled]',
        ]);
    }
}
