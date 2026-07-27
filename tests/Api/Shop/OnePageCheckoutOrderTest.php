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

use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart;
use Sylius\Bundle\ApiBundle\Command\Cart\PickupCart;
use Symfony\Component\Messenger\MessageBusInterface;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Api\JsonApiTestCase;

final class OnePageCheckoutOrderTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testGetsLimitedOrderInformationForAGuestCart(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
            'product.yaml',
            'shipping_method.yaml',
            'payment_method.yaml',
        ]);

        $tokenValue = $this->pickUpCartWithItem();

        $this->requestGet(sprintf('/api/v2/shop/orders/%s/one-page', $tokenValue));

        $this->assertResponse($this->client()->getResponse(), 'shop/one_page_checkout/order');
    }

    public function testReturnsNotFoundForANonExistingOrder(): void
    {
        $this->loadFixturesFromFiles([
            'channel.yaml',
            'country.yaml',
        ]);

        $this->requestGet('/api/v2/shop/orders/NOT_EXISTING_TOKEN/one-page');

        self::assertResponseStatusCodeSame(404);
    }

    private function pickUpCartWithItem(): string
    {
        /** @var MessageBusInterface $commandBus */
        $commandBus = $this->get('sylius.command_bus');

        $tokenValue = 'ONE_PAGE_ORDER_TOKEN';

        $commandBus->dispatch(new PickupCart(
            channelCode: 'WEB',
            localeCode: 'en_US',
            tokenValue: $tokenValue,
        ));

        $commandBus->dispatch(new AddItemToCart(
            orderTokenValue: $tokenValue,
            productVariantCode: 'MUG',
            quantity: 1,
        ));

        return $tokenValue;
    }
}
