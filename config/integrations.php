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

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

$isBundleActive = static function (string $bundleName, array $bundles = []): bool {
    return \in_array($bundleName, array_keys($bundles), true);
};

return static function (ContainerConfigurator $configurator, ContainerBuilder $container) use ($isBundleActive): void {
    /** @var array<string> $bundles */
    $bundles = $container->getParameter('kernel.bundles');

    if ($isBundleActive('winzouStateMachineBundle', $bundles)) {
        $configurator->import('integrations/winzou_state_machine/config.yaml');
        $configurator->import('integrations/winzou_state_machine/services.php');
    }

    if ($isBundleActive('SyliusApiBundle', $bundles)) {
        $configurator->import('integrations/sylius_api/config.yaml');
        $configurator->import('integrations/sylius_api/services.php');
    }
};
