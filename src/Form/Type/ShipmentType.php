<?php

declare(strict_types=1);

namespace Sylius\LiveOnePageCheckoutPlugin\Form\Type;

use Sylius\Bundle\ShippingBundle\Form\Type\ShippingMethodChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ShipmentType extends AbstractType
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
        return 'sylius_live_one_page_checkout_shipment';
    }

    private function addMethod(FormEvent $event, mixed $shipment): void
    {
        $event->getForm()->add('method', ShippingMethodChoiceType::class, [
            'required' => true,
            'label' => 'sylius.form.checkout.shipping_method',
            'subject' => $shipment,
            'expanded' => true,
        ]);
    }

    private function discardUnsupportedMethod(FormEvent $event): void
    {
        $data = $event->getData();
        $code = is_array($data) ? ($data['method'] ?? null) : null;

        if (null === $code) {
            return;
        }

        $supported = $event->getForm()->get('method')->getConfig()->getOption('choices');

        if (!in_array($code, array_map(static fn ($method) => $method->getCode(), $supported), true)) {
            $data['method'] = null;
            $event->setData($data);
        }
    }
}
