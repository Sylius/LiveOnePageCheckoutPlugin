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

namespace Sylius\LiveOnePageCheckoutPlugin\Api\Security\Voter;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\LiveOnePageCheckoutPlugin\Api\Command\Checkout;
use Sylius\LiveOnePageCheckoutPlugin\Checker\CheckoutEnabledCheckerInterface;
use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @extends Voter<string, Checkout>
 */
final class CheckoutAvailabilityVoter extends Voter implements CacheableVoterInterface
{
    public const AVAILABLE = 'SYLIUS_ONE_PAGE_CHECKOUT_AVAILABLE';

    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CheckoutEnabledCheckerInterface $checkoutEnabledChecker,
    ) {
    }

    public function supportsAttribute(string $attribute): bool
    {
        return $attribute === self::AVAILABLE;
    }

    public function supportsType(string $subjectType): bool
    {
        return is_a($subjectType, Checkout::class, true);
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::AVAILABLE && $subject instanceof Checkout;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var Checkout $subject */
        $order = $this->orderRepository->findCartByTokenValue($subject->orderTokenValue);

        if (!$order instanceof OrderInterface) {
            return false;
        }

        /** @var ChannelOnePageCheckoutAwareInterface $channel */
        $channel = $order->getChannel();

        return $this->checkoutEnabledChecker->isEnabled($channel);
    }
}
