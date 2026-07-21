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

namespace Tests\Sylius\LiveOnePageCheckoutPlugin\Integration\StateMachine;

use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Channel;
use Tests\Sylius\LiveOnePageCheckoutPlugin\Entity\Order;

final class OrderCheckoutCompletionGuardTest extends KernelTestCase
{
    private StateMachineInterface $stateMachine;

    protected function setUp(): void
    {
        self::bootKernel();

        $stateMachine = self::getContainer()->get('sylius_abstraction.state_machine');
        self::assertInstanceOf(StateMachineInterface::class, $stateMachine);

        $this->stateMachine = $stateMachine;
    }

    public function testAllowsCartCompletionWhenChannelHasOnePageCheckoutEnabled(): void
    {
        $order = $this->createCartOrder(onePageCheckoutEnabled: true);

        self::assertTrue($this->canComplete($order));
    }

    public function testBlocksCartCompletionWhenChannelHasOnePageCheckoutDisabled(): void
    {
        $order = $this->createCartOrder(onePageCheckoutEnabled: false);

        self::assertFalse($this->canComplete($order));
    }

    private function canComplete(Order $order): bool
    {
        return $this->stateMachine->can(
            $order,
            OrderCheckoutTransitions::GRAPH,
            OrderCheckoutTransitions::TRANSITION_COMPLETE,
        );
    }

    private function createCartOrder(bool $onePageCheckoutEnabled): Order
    {
        $channel = new Channel();
        $channel->setOnePageCheckoutEnabled($onePageCheckoutEnabled);

        $order = new Order();
        $order->setChannel($channel);

        return $order;
    }
}
