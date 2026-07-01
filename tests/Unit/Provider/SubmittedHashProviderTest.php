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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Provider;

use PHPUnit\Framework\TestCase;
use Sylius\LiveOnePageCheckoutPlugin\Form\Type\CheckoutType;
use Sylius\LiveOnePageCheckoutPlugin\Provider\SubmittedHashProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class SubmittedHashProviderTest extends TestCase
{
    public function testReturnsEmptyStringWhenThereIsNoCurrentRequest(): void
    {
        $provider = new SubmittedHashProvider(new RequestStack());

        self::assertSame('', $provider->getSubmittedHash());
    }

    public function testReturnsSubmittedHashFromCurrentRequest(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/checkout-one-page', 'PUT', [
            CheckoutType::NAME => ['stateHash' => 'abc123'],
        ]));

        $provider = new SubmittedHashProvider($requestStack);

        self::assertSame('abc123', $provider->getSubmittedHash());
    }

    public function testReturnsEmptyStringWhenStateHashIsMissingFromSubmittedForm(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/checkout-one-page', 'PUT', [
            CheckoutType::NAME => [],
        ]));

        $provider = new SubmittedHashProvider($requestStack);

        self::assertSame('', $provider->getSubmittedHash());
    }
}
