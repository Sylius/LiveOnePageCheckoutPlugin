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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Api\Admin;

use Tests\Sylius\LiveOnePageCheckoutPlugin\Api\JsonApiTestCase;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Channel;

final class ChannelOnePageCheckoutTest extends JsonApiTestCase
{
    protected function setUp(): void
    {
        $this->setUpDefaultPostHeaders();

        parent::setUp();
    }

    public function testExposesOnePageCheckoutFlagOnAdminChannelShow(): void
    {
        $this->loadFixturesFromFiles(['channel.yaml', 'admin_user.yaml']);

        $this->requestGet(
            uri: '/api/v2/admin/channels/WEB',
            headers: $this->adminAuthorizationHeader(),
        );

        $response = $this->client()->getResponse();
        self::assertSame(200, $response->getStatusCode());

        /** @var array{onePageCheckoutEnabled: bool} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertTrue($content['onePageCheckoutEnabled']);
    }

    public function testExposesOnePageCheckoutFlagOnAdminChannelCollection(): void
    {
        $this->loadFixturesFromFiles(['channel.yaml', 'admin_user.yaml']);

        $this->requestGet(
            uri: '/api/v2/admin/channels',
            headers: $this->adminAuthorizationHeader(),
        );

        $response = $this->client()->getResponse();
        self::assertSame(200, $response->getStatusCode());

        /** @var array{'hydra:member': array<int, array{code: string, onePageCheckoutEnabled: bool}>} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertSame('WEB', $content['hydra:member'][0]['code']);
        self::assertTrue($content['hydra:member'][0]['onePageCheckoutEnabled']);
    }

    public function testDisablesOnePageCheckoutThroughAdminApi(): void
    {
        $this->loadFixturesFromFiles(['channel.yaml', 'admin_user.yaml']);

        $this->requestPut(
            uri: '/api/v2/admin/channels/WEB',
            body: ['onePageCheckoutEnabled' => false],
            headers: $this->adminAuthorizationHeader(),
        );

        $response = $this->client()->getResponse();
        self::assertSame(200, $response->getStatusCode());

        /** @var array{onePageCheckoutEnabled: bool} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertFalse($content['onePageCheckoutEnabled']);
        self::assertFalse($this->findChannel('WEB')->isOnePageCheckoutEnabled());
    }

    public function testEnablesOnePageCheckoutThroughAdminApi(): void
    {
        $this->loadFixturesFromFiles(['channel_disabled.yaml', 'admin_user.yaml']);

        $this->requestPut(
            uri: '/api/v2/admin/channels/WEB',
            body: ['onePageCheckoutEnabled' => true],
            headers: $this->adminAuthorizationHeader(),
        );

        $response = $this->client()->getResponse();
        self::assertSame(200, $response->getStatusCode());

        /** @var array{onePageCheckoutEnabled: bool} $content */
        $content = json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR);

        self::assertTrue($content['onePageCheckoutEnabled']);
        self::assertTrue($this->findChannel('WEB')->isOnePageCheckoutEnabled());
    }

    /** @return array<string, string> */
    private function adminAuthorizationHeader(): array
    {
        return [
            'HTTP_AUTHORIZATION' => sprintf(
                'Bearer %s',
                $this->generateJwtTokenForAdminUserEmail('admin@example.com'),
            ),
        ];
    }

    private function findChannel(string $code): Channel
    {
        $this->entityManager()->clear();

        $channel = $this->getRepository(Channel::class)->findOneBy(['code' => $code]);
        self::assertInstanceOf(Channel::class, $channel);

        return $channel;
    }
}
