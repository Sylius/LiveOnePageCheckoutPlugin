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

use Tests\Sylius\LiveOnePageCheckoutPlugin\Api\JsonApiTestCase;

final class ChannelOnePageCheckoutTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testExposesEnabledOnePageCheckoutFlagOnChannelShow(): void
    {
        $this->loadFixturesFromFiles(['channel.yaml']);

        $this->requestGet(uri: '/api/v2/shop/channels/WEB');

        $response = $this->client()->getResponse();
        self::assertSame(200, $response->getStatusCode());

        /** @var array{onePageCheckoutEnabled: bool} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertTrue($content['onePageCheckoutEnabled']);
    }

    public function testExposesDisabledOnePageCheckoutFlagOnChannelShow(): void
    {
        $this->loadFixturesFromFiles(['channel_disabled.yaml']);

        $this->requestGet(uri: '/api/v2/shop/channels/WEB');

        $response = $this->client()->getResponse();
        self::assertSame(200, $response->getStatusCode());

        /** @var array{onePageCheckoutEnabled: bool} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertFalse($content['onePageCheckoutEnabled']);
    }

    public function testExposesOnePageCheckoutFlagOnChannelCollection(): void
    {
        $this->loadFixturesFromFiles(['channel.yaml']);

        $this->requestGet(uri: '/api/v2/shop/channels');

        $response = $this->client()->getResponse();
        self::assertSame(200, $response->getStatusCode());

        /** @var array{'hydra:member': array<int, array{code: string, onePageCheckoutEnabled: bool}>} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('WEB', $content['hydra:member'][0]['code']);
        self::assertTrue($content['hydra:member'][0]['onePageCheckoutEnabled']);
    }
}
