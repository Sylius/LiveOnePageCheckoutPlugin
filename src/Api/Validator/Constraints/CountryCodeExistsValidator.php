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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Validator\Constraints;

use Sylius\Component\Addressing\Model\AddressInterface;
use Sylius\Component\Addressing\Model\CountryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Webmozart\Assert\Assert;

final class CountryCodeExistsValidator extends ConstraintValidator
{
    /**
     * @param RepositoryInterface<CountryInterface> $countryRepository
     */
    public function __construct(
        private RepositoryInterface $countryRepository,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        Assert::isInstanceOf($value, Checkout::class);

        /** @var CountryCodeExists $constraint */
        Assert::isInstanceOf($constraint, CountryCodeExists::class);

        $this->validateAddress($value->billingAddress, 'billingAddress', $constraint);
        $this->validateAddress($value->shippingAddress, 'shippingAddress', $constraint);
    }

    private function validateAddress(?AddressInterface $address, string $path, CountryCodeExists $constraint): void
    {
        if (null === $address) {
            return;
        }

        $countryCode = $address->getCountryCode();

        if (null === $countryCode || '' === trim($countryCode)) {
            return;
        }

        $country = $this->countryRepository->findOneBy(['code' => $countryCode]);

        if (null === $country) {
            $this->context
                ->buildViolation($constraint->message)
                ->atPath("$path.countryCode")
                ->setParameter('%countryCode%', $countryCode)
                ->addViolation()
            ;
        }
    }
}
