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

namespace Sylius\LiveOnePageCheckoutPlugin\Integration\Mollie\Shop\Form\Extension;

use Sylius\LiveOnePageCheckoutPlugin\Form\Type\PaymentType;
use Sylius\MolliePlugin\Form\Type\PaymentMollieType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints\Valid;

final class PaymentTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('details', PaymentMollieType::class, [
            'validation_groups' => ['sylius'],
            'constraints' => [
                new Valid(),
            ],
        ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $form = $event->getForm();

            if (!$form->getRoot()->has('billingAddress')) {
                return;
            }

            $form->remove('details');
            $form->add('details', PaymentMollieType::class, [
                'validation_groups' => ['sylius'],
                'constraints' => [
                    new Valid(),
                ],
            ]);
        });
    }

    public static function getExtendedTypes(): array
    {
        return [PaymentType::class];
    }
}
