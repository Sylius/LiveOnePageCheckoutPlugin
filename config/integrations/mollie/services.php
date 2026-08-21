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

use Sylius\LiveOnePageCheckoutPlugin\Form\Type\PaymentType;
use Sylius\LiveOnePageCheckoutPlugin\Integration\Mollie\Shop\Form\Extension\PaymentTypeExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services
        ->set('sylius_live_one_page_checkout.integration.mollie.shop.form.extension.payment_type', PaymentTypeExtension::class)
        ->tag('form.type_extension', ['extended_type' => PaymentType::class])
    ;
};
