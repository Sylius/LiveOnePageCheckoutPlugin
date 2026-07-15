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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Factory;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\DTO\AddressPreview;

final readonly class AddressPreviewFactory implements AddressPreviewFactoryInterface
{
    public function create(?AddressInterface $address): ?AddressPreview
    {
        if (null === $address) {
            return null;
        }

        return new AddressPreview(
            $address->getFirstName(),
            $address->getLastName(),
            $address->getCompany(),
            $address->getStreet(),
            $address->getCity(),
            $address->getPostcode(),
            $address->getCountryCode(),
            $address->getProvinceCode(),
            $address->getProvinceName(),
            $address->getPhoneNumber(),
        );
    }
}
