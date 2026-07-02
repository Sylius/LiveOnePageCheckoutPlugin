<?php

declare(strict_types=1);

namespace Sylius\LiveOnePageCheckoutPlugin\Form\Type;

use Sylius\Bundle\CoreBundle\Form\Type\AddressChoiceType;
use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class DefaultAddressChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'expanded' => true,
                'mapped' => false,
                'choice_label' => fn (AddressInterface $address) => sprintf(
                    '%s, %s, %s, %s, %s',
                    $address->getFullName(),
                    $address->getStreet(),
                    $address->getCity(),
                    $address->getPostcode(),
                    $address->getCountryCode(),
                ),
                'preferred_choices' => function (Options $options): array {
                    $customer = $options['customer'] ?? null;

                    return $customer instanceof CustomerInterface && null !== $customer->getDefaultAddress()
                        ? [$customer->getDefaultAddress()]
                        : [];
                },
            ])
        ;
    }

    public function getParent(): string
    {
        return AddressChoiceType::class;
    }
}
