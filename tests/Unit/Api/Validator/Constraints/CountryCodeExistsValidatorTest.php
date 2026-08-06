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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Unit\Api\Validator\Constraints;

use PHPUnit\Framework\MockObject\MockObject;
use Sylius\Component\Addressing\Model\Address;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\CountryCodeExists;
use Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints\CountryCodeExistsValidator;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Validator\ConstraintValidatorInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @extends ConstraintValidatorTestCase<CountryCodeExistsValidator>
 */
final class CountryCodeExistsValidatorTest extends ConstraintValidatorTestCase
{
    /** @var RepositoryInterface<CountryInterface>&MockObject */
    private RepositoryInterface&MockObject $countryRepository;

    protected function setUp(): void
    {
        $this->countryRepository = $this->createMock(RepositoryInterface::class);

        parent::setUp();
    }

    protected function createValidator(): ConstraintValidatorInterface
    {
        return new CountryCodeExistsValidator($this->countryRepository);
    }

    public function testNoViolationWhenNoAddressesProvided(): void
    {
        $command = new Checkout(orderTokenValue: 'token123');

        $this->countryRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new CountryCodeExists());

        $this->assertNoViolation();
    }

    public function testNoViolationWhenCountryCodeIsBlank(): void
    {
        $address = new Address();
        $command = new Checkout(orderTokenValue: 'token123', billingAddress: $address);

        $this->countryRepository->expects(self::never())->method('findOneBy');

        $this->validator->validate($command, new CountryCodeExists());

        $this->assertNoViolation();
    }

    public function testViolationWhenShippingAddressCountryCodeDoesNotExist(): void
    {
        $address = new Address();
        $address->setCountryCode('XX');
        $command = new Checkout(orderTokenValue: 'token123', shippingAddress: $address);

        $this->countryRepository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => 'XX'])
            ->willReturn(null)
        ;

        $this->validator->validate($command, new CountryCodeExists());

        $this->buildViolation('sylius.country.not_exist')
            ->atPath('property.path.shippingAddress.countryCode')
            ->setParameter('%countryCode%', 'XX')
            ->assertRaised()
        ;
    }

    public function testNoViolationWhenBothAddressesHaveExistingCountryCode(): void
    {
        $billingAddress = new Address();
        $billingAddress->setCountryCode('US');
        $shippingAddress = new Address();
        $shippingAddress->setCountryCode('US');

        $command = new Checkout(
            orderTokenValue: 'token123',
            billingAddress: $billingAddress,
            shippingAddress: $shippingAddress,
        );

        $this->countryRepository->expects(self::exactly(2))
            ->method('findOneBy')
            ->with(['code' => 'US'])
            ->willReturn($this->createMock(CountryInterface::class))
        ;

        $this->validator->validate($command, new CountryCodeExists());

        $this->assertNoViolation();
    }
}
