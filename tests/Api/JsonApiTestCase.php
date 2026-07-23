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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Api;

use ApiTestCase\JsonApiTestCase as BaseJsonApiTestCase;
use Doctrine\Persistence\ObjectRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\Before;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Webmozart\Assert\Assert;

abstract class JsonApiTestCase extends BaseJsonApiTestCase
{
    /** @var array<string, string> */
    private array $defaultPostHeaders = [];

    #[Before]
    public function setUpResponsePaths(): void
    {
        $this->dataFixturesPath = __DIR__ . '/DataFixtures/ORM';
        $this->expectedResponsesPath = __DIR__ . '/Responses';
    }

    protected function setUpDefaultPostHeaders(): void
    {
        $this->defaultPostHeaders = [
            'HTTP_ACCEPT' => 'application/ld+json',
            'CONTENT_TYPE' => 'application/ld+json',
        ];
    }

    protected function client(): KernelBrowser
    {
        Assert::notNull($this->client);

        return $this->client;
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, string> $headers
     */
    protected function requestPost(string $uri, ?array $body = null, array $headers = []): Crawler
    {
        $headers = array_merge($this->defaultPostHeaders, $headers);

        return $this->client()->request(
            method: 'POST',
            uri: $uri,
            server: $headers,
            content: is_array($body) ? json_encode($body, \JSON_THROW_ON_ERROR) : null,
        );
    }

    /**
     * @param array<string, string> $headers
     */
    protected function requestGet(string $uri, array $headers = []): Crawler
    {
        $headers = array_merge($this->defaultPostHeaders, $headers);

        return $this->client()->request(
            method: 'GET',
            uri: $uri,
            server: $headers,
        );
    }

    protected function generateJwtTokenForUser(ShopUserInterface $user): string
    {
        /** @var JWTTokenManagerInterface $jwtManager */
        $jwtManager = $this->get(JWTTokenManagerInterface::class);

        return $jwtManager->create($user);
    }

    protected function generateJwtTokenForShopUserEmail(string $email): string
    {
        /** @var UserRepositoryInterface<ShopUserInterface> $shopUserRepository */
        $shopUserRepository = $this->get('sylius.repository.shop_user');
        $shopUser = $shopUserRepository->findOneByEmail($email);
        Assert::isInstanceOf($shopUser, ShopUserInterface::class);

        return $this->generateJwtTokenForUser($shopUser);
    }

    /**
     * @return ObjectRepository<object>
     */
    protected function getRepository(string $className): ObjectRepository
    {
        return $this->get('doctrine.orm.entity_manager')->getRepository($className);
    }
}
