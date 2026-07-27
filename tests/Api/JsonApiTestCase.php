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
use Sylius\Bundle\ApiBundle\Command\Cart\AddItemToCart;
use Sylius\Bundle\ApiBundle\Command\Cart\PickupCart;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Sylius\Component\Core\Repository\CustomerRepositoryInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
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

    protected function pickUpCartWithItem(
        string $tokenValue = 'CART_TOKEN',
        string $productVariantCode = 'MUG',
        int $quantity = 1,
        ?string $customerEmail = null,
    ): string {
        /** @var MessageBusInterface $commandBus */
        $commandBus = $this->get('sylius.command_bus');

        $commandBus->dispatch(new PickupCart(
            channelCode: 'WEB',
            localeCode: 'en_US',
            tokenValue: $tokenValue,
        ));

        $commandBus->dispatch(new AddItemToCart(
            orderTokenValue: $tokenValue,
            productVariantCode: $productVariantCode,
            quantity: $quantity,
        ));

        if (null !== $customerEmail) {
            $this->assignCustomerToCart($tokenValue, $customerEmail);
        }

        return $tokenValue;
    }

    protected function assignCustomerToCart(string $tokenValue, string $customerEmail): void
    {
        /** @var OrderRepositoryInterface<OrderInterface> $orderRepository */
        $orderRepository = $this->get('sylius.repository.order');
        $order = $orderRepository->findCartByTokenValue($tokenValue);
        Assert::isInstanceOf($order, OrderInterface::class);

        /** @var CustomerRepositoryInterface<CustomerInterface> $customerRepository */
        $customerRepository = $this->get('sylius.repository.customer');
        $customer = $customerRepository->findOneBy(['email' => $customerEmail]);
        Assert::isInstanceOf($customer, CustomerInterface::class);

        $order->setCustomerWithAuthorization($customer);

        $this->get('doctrine.orm.entity_manager')->flush();
    }

    /**
     * Asserts that response contains exactly the expected violations (exact match).
     *
     * @param array<array-key, mixed> $expectedViolations
     *
     * @throws \Exception
     */
    protected function assertResponseViolations(array $expectedViolations): void
    {
        $this->assertViolationResponseHeaders();

        $response = $this->client()->getResponse();
        $responseContent = $response->getContent() ?: '';
        $this->assertNotEmpty($responseContent);

        $decoded = json_decode($responseContent, true);
        Assert::isArray($decoded);

        $actualViolations = $decoded['violations'];
        Assert::isArray($actualViolations);

        $actualDescription = $decoded['hydra:description'];

        array_walk($actualViolations, function (&$item): void {
            unset($item['code']);
        });

        $mappedViolations = array_map(function (mixed $violation): string {
            Assert::isArray($violation);

            if (empty($violation['propertyPath'])) {
                return $violation['message'];
            }

            return $violation['propertyPath'] . ': ' . $violation['message'];
        }, $expectedViolations);

        $expectedDescription = implode("\n", $mappedViolations);

        $expected = [
            '@context' => '/api/v2/contexts/ConstraintViolationList',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => $expectedDescription,
            'violations' => $expectedViolations,
        ];

        $actual = [
            '@context' => '/api/v2/contexts/ConstraintViolationList',
            '@type' => 'ConstraintViolationList',
            'hydra:title' => 'An error occurred',
            'hydra:description' => $actualDescription,
            'violations' => $actualViolations,
        ];

        $this->assertSame($expected, $actual);
    }

    private function assertViolationResponseHeaders(): void
    {
        $response = $this->client()->getResponse();

        if (isset($_SERVER['OPEN_ERROR_IN_BROWSER']) && true === $_SERVER['OPEN_ERROR_IN_BROWSER']) {
            $this->showErrorInBrowserIfOccurred($response);
        }

        $this->assertResponseCode($response, Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertJsonHeader($response);
    }
}
