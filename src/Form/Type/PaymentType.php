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

namespace Sylius\LiveOnePageCheckoutPlugin\Form\Type;

use Sylius\Bundle\PaymentBundle\Form\Type\PaymentMethodChoiceType;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PaymentType extends AbstractType
{
    public function __construct(private string $dataClass)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, fn (FormEvent $event) => $this->addMethod($event, $event->getData()))
            ->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
                $this->addMethod($event, $event->getForm()->getData());
                $this->discardUnsupportedMethod($event);
            })
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => $this->dataClass,
            ])
        ;
    }

    public function getBlockPrefix(): string
    {
        return 'sylius_live_one_page_checkout_payment';
    }

    private function addMethod(FormEvent $event, mixed $payment): void
    {
        $event->getForm()->add('method', PaymentMethodChoiceType::class, [
            'label' => 'sylius.form.checkout.payment_method',
            'subject' => $payment,
            'expanded' => true,
        ]);
    }

    private function discardUnsupportedMethod(FormEvent $event): void
    {
        /** @var array<string, mixed> $data */
        $data = $event->getData();
        $code = $data['method'] ?? null;

        if (null === $code) {
            return;
        }

        $supported = $event->getForm()->get('method')->getConfig()->getOption('choices');
        $supportedCodes = array_map(
            static fn (mixed $method): ?string => $method instanceof PaymentMethodInterface ? $method->getCode() : null,
            is_array($supported) ? $supported : [],
        );

        if (!in_array($code, $supportedCodes, true)) {
            $data['method'] = null;
            $event->setData($data);
        }
    }
}
