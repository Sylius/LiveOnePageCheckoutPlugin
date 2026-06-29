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

namespace Sylius\LiveOnePageCheckoutPlugin\Form\Extension\Admin;

use Sylius\Bundle\AdminBundle\Form\Type\ChannelType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

final class ChannelTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('onePageCheckoutEnabled', CheckboxType::class, [
            'label' => 'sylius_live_one_page_checkout.form.channel.one_page_checkout_enabled',
            'help' => 'sylius_live_one_page_checkout.form.channel.one_page_checkout_enabled_help',
            'required' => false,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        return [ChannelType::class];
    }
}
