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

use Behat\Config\Config;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Profile;
use Behat\Config\Suite;

return (new Config())
    ->withProfile(
        (new Profile('default'))
        ->withSuite(
            (new Suite('ui_one_page_checkout_managing_channels'))
            ->withContexts(
                'sylius.behat.context.hook.bad_gateway',
                'sylius.behat.context.hook.doctrine_orm',
                'sylius.behat.context.hook.session',
            )
            ->withContexts(
                'sylius.behat.context.transform.channel',
                'sylius.behat.context.transform.shared_storage',
            )
            ->withContexts(
                'sylius.behat.context.setup.admin_security',
                'sylius.behat.context.setup.channel',
            )
            ->withContexts(
                'sylius.behat.context.ui.admin.managing_channels',
                'sylius.behat.context.ui.admin.notification',
                'sylius.behat.context.ui.save',
            )
            ->withContexts(
                'sylius_live_one_page_checkout.behat.context.setup.channel',
                'sylius_live_one_page_checkout.behat.context.ui.admin.managing_channels',
            )
            ->withFilter(new TagFilter('@one_page_checkout_managing_channels&&@ui')),
        ),
    )
;
