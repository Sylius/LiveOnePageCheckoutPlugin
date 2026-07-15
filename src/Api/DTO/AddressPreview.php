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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\DTO;

class AddressPreview
{
    public function __construct(
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $company,
        public readonly ?string $street,
        public readonly ?string $city,
        public readonly ?string $postcode,
        public readonly ?string $countryCode,
        public readonly ?string $provinceCode,
        public readonly ?string $provinceName,
        public readonly ?string $phoneNumber,
    ) {
    }
}
