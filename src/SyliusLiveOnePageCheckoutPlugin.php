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

namespace Sylius\LiveOnePageCheckoutPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Sylius\LiveOnePageCheckoutPlugin\DependencyInjection\Compiler\AddOrderCheckoutWorkflowTransitionPass;
use Sylius\LiveOnePageCheckoutPlugin\DependencyInjection\Compiler\AddOrderProcessorTagPass;
use Sylius\LiveOnePageCheckoutPlugin\DependencyInjection\Compiler\RegisterOnePageOrderProcessorsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class SyliusLiveOnePageCheckoutPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AddOrderCheckoutWorkflowTransitionPass());
        $container->addCompilerPass(new RegisterOnePageOrderProcessorsPass());
        $container->addCompilerPass(
            new AddOrderProcessorTagPass(
                [
                    'sylius.order_processing.order_adjustments_clearer' => 60,
                    'sylius.order_processing.order_prices_recalculator' => 50,
                    'sylius.order_processing.shipping_charges_processor' => 30,
                    'sylius.order_processing.order_promotion_processor' => 20,
                    'sylius.order_processing.order_taxes_processor' => 10,
                    'sylius.order_processing.order_payment_processor.checkout' => 0,
                ],
            ),
            priority: 10,
        );
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
