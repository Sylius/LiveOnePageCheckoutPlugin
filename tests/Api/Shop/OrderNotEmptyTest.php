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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Api\Shop;

use Sylius\Bundle\ApiBundle\Command\Cart\PickupCart;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Api\JsonApiTestCase;

final class OrderNotEmptyTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testDoesNotAllowPreviewOfAnEmptyCart(): void
    {
        $this->loadFixturesFromFiles(['channel.yaml']);

        $tokenValue = $this->pickUpEmptyCart('ORDER_NOT_EMPTY_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/preview', $tokenValue),
            body: [],
        );

        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => 'An empty order cannot be processed.'],
        ]);
    }

    public function testDoesNotAllowCompletingAnEmptyCart(): void
    {
        $this->loadFixturesFromFiles(['channel.yaml', 'country.yaml']);

        $tokenValue = $this->pickUpEmptyCart('ORDER_NOT_EMPTY_TOKEN');

        $this->requestPatch(
            uri: sprintf('/api/v2/shop/orders/%s/one-page/complete', $tokenValue),
            body: [
                'email' => 'shop@example.com',
                'shippingAddress' => $this->address(),
                'billingAddress' => $this->address(),
            ],
        );

        $this->assertResponseViolations([
            ['propertyPath' => '', 'message' => 'An empty order cannot be processed.'],
        ]);
    }

    private function pickUpEmptyCart(string $tokenValue): string
    {
        /** @var MessageBusInterface $commandBus */
        $commandBus = $this->get('sylius.command_bus');

        $commandBus->dispatch(new PickupCart(
            channelCode: 'WEB',
            localeCode: 'en_US',
            tokenValue: $tokenValue,
        ));

        return $tokenValue;
    }

    /**
     * @return array<string, string>
     */
    private function address(): array
    {
        return [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'city' => 'New York',
            'street' => 'Avenue',
            'postcode' => '90000',
            'countryCode' => 'US',
        ];
    }
}
