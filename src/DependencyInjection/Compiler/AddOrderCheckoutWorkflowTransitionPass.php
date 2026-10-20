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

use Sylius\Component\Core\OrderCheckoutStates;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Workflow\Transition;

final class AddOrderCheckoutWorkflowTransitionPass implements CompilerPassInterface
{
    private const WORKFLOW_DEFINITION_ID = 'state_machine.' . OrderCheckoutTransitions::GRAPH . '.definition';

    /** @var array<array{name: string, from: string, to: string}> */
    private const REQUIRED_TRANSITIONS = [
        [
            'name' => OrderCheckoutTransitions::TRANSITION_COMPLETE,
            'from' => OrderCheckoutStates::STATE_CART,
            'to' => OrderCheckoutStates::STATE_COMPLETED,
        ],
    ];

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::WORKFLOW_DEFINITION_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::WORKFLOW_DEFINITION_ID);

        /** @var array<mixed> $transitions */
        $transitions = $definition->getArgument(1);

        $existingTransitions = $this->extractExistingTransitions($transitions);

        foreach (self::REQUIRED_TRANSITIONS as $requiredTransition) {
            $transitionKey = $this->createTransitionKey(
                $requiredTransition['name'],
                $requiredTransition['from'],
                $requiredTransition['to'],
            );

            if (in_array($transitionKey, $existingTransitions, true)) {
                continue;
            }

            $transitions[] = $this->createTransitionDefinition($requiredTransition);
        }

        $definition->setArgument(1, $transitions);
    }

    /**
     * @param array<mixed> $transitions
     *
     * @return array<string>
     */
    private function extractExistingTransitions(array $transitions): array
    {
        $existingTransitions = [];

        foreach ($transitions as $transition) {
            if (!$transition instanceof Definition) {
                continue;
            }

            $arguments = $transition->getArguments();
            if (count($arguments) < 3) {
                continue;
            }

            [$name, $froms, $tos] = $arguments;
            if (!is_string($name)) {
                continue;
            }

            foreach ((array) $froms as $from) {
                foreach ((array) $tos as $to) {
                    if (!is_string($from) || !is_string($to)) {
                        continue;
                    }

                    $existingTransitions[] = $this->createTransitionKey($name, $from, $to);
                }
            }
        }

        return $existingTransitions;
    }

    private function createTransitionKey(string $name, string $from, string $to): string
    {
        return sprintf('%s:%s:%s', $name, $from, $to);
    }

    /** @param array{name: string, from: string, to: string} $transitionConfig */
    private function createTransitionDefinition(array $transitionConfig): Definition
    {
        $transition = new Definition(Transition::class);
        $transition->setArguments([
            $transitionConfig['name'],
            [$transitionConfig['from']],
            [$transitionConfig['to']],
        ]);

        return $transition;
    }
}
