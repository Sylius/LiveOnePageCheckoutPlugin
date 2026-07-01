<?php

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

         return (string) ($formData['stateHash'] ?? '');
    }
}