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

namespace Sylius\LiveOnePageCheckoutPlugin\Provider;

use Sylius\LiveOnePageCheckoutPlugin\Form\Type\CheckoutType;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class SubmittedHashProvider implements SubmittedHashProviderInterface
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function getSubmittedHash(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return '';
        }

        $formData = $request->request->all(CheckoutType::NAME);
        $stateHash = $formData['stateHash'] ?? '';

        return is_string($stateHash) ? $stateHash : '';
    }
}
