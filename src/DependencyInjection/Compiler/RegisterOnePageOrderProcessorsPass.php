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

use Sylius\Bundle\ResourceBundle\DependencyInjection\Compiler\PrioritizedCompositeServicePass;

final class RegisterOnePageOrderProcessorsPass extends PrioritizedCompositeServicePass
{
    public const PROCESSOR_SERVICE_TAG = 'sylius_live_one_page_checkout.order_processor';

    public function __construct()
    {
        parent::__construct(
            'sylius_live_one_page_checkout.order_processing.order_processor',
            'sylius_live_one_page_checkout.order_processing.order_processor.composite',
            self::PROCESSOR_SERVICE_TAG,
            'addProcessor',
        );
    }
}
