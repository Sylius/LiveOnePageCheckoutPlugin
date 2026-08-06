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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\CommandHandler;

use ApiPlatform\Validator\ValidatorInterface;
use Sylius\Abstraction\StateMachine\StateMachineInterface;
use Sylius\Bundle\ApiBundle\Event\OrderCompleted;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderCheckoutTransitions;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Applier\CheckoutApplierInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Api\Exception\OrderStateMismatchException;
use Sylius\LiveOnePageCheckoutPlugin\Checker\OrderConsistencyCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Ensurer\OrderObjectsEnsurerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;
use Webmozart\Assert\Assert;

final readonly class CompleteCheckoutHandler
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     * @param list<string> $validationGroups
     */
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderObjectsEnsurerInterface $orderObjectsEnsurer,
        private CheckoutApplierInterface $checkoutApplier,
        private OrderProcessorInterface $orderProcessor,
        private OrderConsistencyCheckerInterface $consistencyChecker,
        private StateMachineInterface $stateMachine,
        private MessageBusInterface $eventBus,
        private ValidatorInterface $validator,
        private array $validationGroups,
    ) {
    }

    public function __invoke(Checkout $command): OrderInterface
    {
        $order = $this->orderRepository->findCartByTokenValue($command->orderTokenValue);

        if (!$order instanceof OrderInterface) {
            throw new NotFoundHttpException('Cart has not been found.');
        }

        $this->orderObjectsEnsurer->ensure($order);
        $this->checkoutApplier->apply($order, $command);

        $this->validator->validate($order, ['groups' => $this->validationGroups]);

        Assert::notNull($order->getCustomer(), 'Please enter your email before completing the order.');

        $this->orderProcessor->process($order);

        if (!$this->consistencyChecker->isConsistent($order, $command->hash ?? '')) {
            throw new OrderStateMismatchException();
        }

        $this->stateMachine->apply($order, OrderCheckoutTransitions::GRAPH, OrderCheckoutTransitions::TRANSITION_COMPLETE);

        $this->eventBus->dispatch(new OrderCompleted($command->orderTokenValue), [new DispatchAfterCurrentBusStamp()]);

        return $order;
    }
}
