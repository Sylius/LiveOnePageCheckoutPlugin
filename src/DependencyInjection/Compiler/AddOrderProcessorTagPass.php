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

namespace Sylius\LiveOnePageCheckoutPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AddOrderProcessorTagPass implements CompilerPassInterface
{
    /** @param array<string, int> $serviceIds map of service id => priority */
    public function __construct(
        private readonly array $serviceIds,
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        foreach ($this->serviceIds as $id => $priority) {
            if (!$container->hasDefinition($id)) {
                continue;
            }

            $container
                ->findDefinition($id)
                ->addTag(RegisterOnePageOrderProcessorsPass::PROCESSOR_SERVICE_TAG, ['priority' => $priority])
            ;
        }
    }
}
