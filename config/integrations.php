<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use winzou\Bundle\StateMachineBundle\winzouStateMachineBundle;

return static function (ContainerConfigurator $configurator, ContainerBuilder $container): void {
    if (class_exists(winzouStateMachineBundle::class)) {
        $configurator->import('integrations/winzou_state_machine/config.yaml');
        $configurator->import('integrations/winzou_state_machine/services.php');
    }
};
