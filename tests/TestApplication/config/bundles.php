<?php

$bundles = [
    Sylius\LiveOnePageCheckoutPlugin\SyliusLiveOnePageCheckoutPlugin::class => ['all' => true],
];

if (class_exists('winzou\Bundle\StateMachineBundle\winzouStateMachineBundle')) {
    $bundles[winzou\Bundle\StateMachineBundle\winzouStateMachineBundle::class] = ['all' => true];
}

return $bundles;