<p align="center">
    <a href="https://sylius.com" target="_blank">
        <picture>
          <source media="(prefers-color-scheme: dark)" srcset="https://media.sylius.com/sylius-logo-800-dark.png">
          <source media="(prefers-color-scheme: light)" srcset="https://media.sylius.com/sylius-logo-800.png">
          <img alt="Sylius Logo." src="https://media.sylius.com/sylius-logo-800.png">
        </picture>
    </a>
</p>

<h1 align="center">Sylius Live One Page Checkout Plugin</h1>

<p align="center">Replace the default multi-step checkout with a single, live-updating one page checkout. Cart, addresses, shipping, payment and order summary live on one page and update as the customer edits them - enabled per channel, so other channels keep the standard Sylius checkout.</p>

## What it does

- Puts cart, addresses, shipping, payment and order summary on **one page**, updating live
  via **Symfony UX Live Components** - no full page reloads as the customer fills the form.
- **Per-channel toggle**: enable "one page checkout" on a channel in the admin panel and only
  that channel switches to the one-page flow; every other channel keeps the standard Sylius
  checkout.
- Adds a direct `cart → completed` checkout transition (guarded so it only fires on enabled
  channels), works with both the **Symfony Workflow** and **winzou** state machines.
- Protects against stale submissions with an order **state hash**: if totals, promotions,
  shipping or payment changed between render and submit, the customer is sent back to review.

See [`CLAUDE.md`](CLAUDE.md) for a full architecture breakdown.

## Requirements

| Package         | Version |
|-----------------|---------|
| PHP             | `^8.2`  |
| `sylius/sylius` | `^2.0`  |

⚙️ Installation
---------------

This guide covers the Live One Page Checkout plugin installation process using Flex recipes and Rector.

Prerequisites
-------------

You need to have installed both SyliusRecipes and SyliusRector. You can find how to install them here:
- [Sylius/SyliusRecipes](https://github.com/Sylius/SyliusRecipes)
- [Sylius/SyliusRector](https://github.com/Sylius/SyliusRector)

Installation guide
------------------

1. Run

    ```bash
    composer require sylius/live-one-page-checkout-plugin --no-scripts
    ```

1. Update `<project_root>/rector.php`

   Depending on your `rector.php` format, apply one of the following:

   - Closure-based configuration:

    ```diff
   + use Sylius\SyliusRector\Set\SyliusLiveOnePageCheckout;

    return static function (RectorConfig $rectorConfig): void {
        // ...
    +    $rectorConfig->sets([
    +        SyliusLiveOnePageCheckout::LIVE_ONE_PAGE_CHECKOUT_PLUGIN_01,
    +    ]);
    };
    ```

   - Fluent `RectorConfig::configure()` configuration:

    ```diff
   + use Sylius\SyliusRector\Set\SyliusLiveOnePageCheckout;

    return RectorConfig::configure()
        // ...
    +    ->withSets([
    +        SyliusLiveOnePageCheckout::LIVE_ONE_PAGE_CHECKOUT_PLUGIN_01,
    +    ]);
    ```

1. Run

    ```bash
    vendor/bin/rector
    ```

1. Ensure the modified resources are configured in `config/packages/_sylius.yaml`:

    ```yaml
    sylius_channel:
        resources:
            channel:
                classes:
                    model: App\Entity\Channel\Channel
   
    sylius_order:
        resources:
            order:
                classes:
                    model: App\Entity\Order\Order
    ```

1. Execute migrations to add the `one_page_checkout_enabled` column:

    ```bash
    bin/console doctrine:migrations:migrate
    ```

1. Run `yarn encore dev` or `yarn encore production`

   The recipe registers the bundle, imports the plugin config and routing, and adds the plugin's
   Shop assets import to `assets/shop/entrypoint.js` for you, so there is nothing to wire by hand.

1. If your application registers custom order processors that affect price, promotions,
   shipping or taxes, tag them accordingly - see [Custom order processors](#custom-order-processors).

1. Rebuild the cache to display all new translations correctly:

    ```bash
    bin/console cache:clear
    ```

1. In the admin panel, edit a channel and tick **Enable one page checkout** to switch it to the
   one page flow.

⌛️ Legacy installation
======================

Don't want to use recipes or Rector? You can still follow the legacy installation guide below:

1. Run

    ```bash
    composer require live-one-page-checkout --no-scripts
    ```

   or

    ```bash
    composer require sylius/live-one-page-checkout-plugin --no-scripts
    ```

1. Add the following line to `config/bundles.php`:

    ```php
    Sylius\LiveOnePageCheckoutPlugin\SyliusLiveOnePageCheckoutPlugin::class => ['all' => true],
    ```

1. The `Channel` entity should implement the following interface:

    ```php
    Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface
    ```

   and use trait:

    ```php
    Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareTrait
    ```

   Final result:

    ```php
    <?php

    declare(strict_types=1);

    namespace App\Entity\Channel;

    use Doctrine\ORM\Mapping as ORM;
    use Sylius\Component\Core\Model\Channel as BaseChannel;
    use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareInterface;
    use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareTrait;

    #[ORM\Entity]
    #[ORM\Table(name: 'sylius_channel')]
    class Channel extends BaseChannel implements ChannelOnePageCheckoutAwareInterface
    {
        use ChannelOnePageCheckoutAwareTrait;
    }
    ```

1. Add configuration of the modified channel resource in `config/packages/_sylius.yaml`:

    ```yaml
    sylius_channel:
        resources:
            channel:
                classes:
                    model: App\Entity\Channel\Channel
    ```

1. The `Order` entity should implement the following interface:

    ```php
    Sylius\LiveOnePageCheckoutPlugin\Entity\OrderAndItemPromotionTotalAwareInterface
    ```

   and use trait:

    ```php
    Sylius\LiveOnePageCheckoutPlugin\Entity\OrderAndItemPromotionTotalAwareTrait
    ```

   Final result:

    ```php
    <?php

    declare(strict_types=1);

    namespace App\Entity\Order;

    use Doctrine\ORM\Mapping as ORM;
    use Sylius\Component\Core\Model\Order as BaseOrder;
    use Sylius\LiveOnePageCheckoutPlugin\Entity\OrderAndItemPromotionTotalAwareInterface;
    use Sylius\LiveOnePageCheckoutPlugin\Entity\OrderAndItemPromotionTotalAwareTrait;

    #[ORM\Entity]
    #[ORM\Table(name: 'sylius_order')]
    class Order extends BaseOrder implements OrderAndItemPromotionTotalAwareInterface
    {
        use OrderAndItemPromotionTotalAwareTrait;
    }
    ```

1. Add configuration of the modified order resource in `config/packages/_sylius.yaml`:

    ```yaml
    sylius_order:
        resources:
            order:
                classes:
                    model: App\Entity\Order\Order
    ```

1. Import the plugin configuration in `config/packages/_sylius.yaml`:

    ```yaml
    imports:
        - { resource: "@SyliusLiveOnePageCheckoutPlugin/config/config.yaml" }
    ```

1. Configure routing in `config/routes/sylius_live_one_page_checkout.yaml`:

    ```yaml
    sylius_live_one_page_checkout:
        resource: "@SyliusLiveOnePageCheckoutPlugin/config/routes.yaml"
    ```

1. Import the plugin's Shop assets into your application's Shop entry file:

    ```javascript
    // assets/shop/entrypoint.js
    import '@vendor/sylius/live-one-page-checkout-plugin/assets/shop/entrypoint';
    ```

1. Execute migrations to add the `one_page_checkout_enabled` column:

    ```bash
    bin/console doctrine:migrations:migrate
    ```

1. Run `yarn encore dev` or `yarn encore production`

1. If your application registers custom order processors that affect price, promotions,
   shipping or taxes, tag them accordingly - see [Custom order processors](#custom-order-processors).

1. Rebuild the cache to display all new translations correctly:

    ```bash
    bin/console cache:clear
    ```

## Custom order processors

The plugin does not reuse Sylius' core order processor composite (`sylius.order_processing.order_processor.composite`)
for its live recalculation. Instead it builds its own composite,
`sylius_live_one_page_checkout.order_processing.order_processor.composite`, populated only with an
explicit whitelist of core processors (adjustments clearer, prices recalculator, shipping charges,
promotion, taxes, checkout payment) - see `SyliusLiveOnePageCheckoutPlugin::build()`. Notably it
**excludes** the core shipping-method default-setter processor, since auto-selecting a shipping
method there conflicts with the live-updating one-page UI.

This means: if your application registers a **custom** order processor that affects the price,
promotions, shipping or taxes (i.e. anything that ends up in the order state hash, see
`Provider/OrderHashDataProvider`), tagging it with the core `sylius.order_processor` tag alone is
**not enough** for the one-page checkout. You must also tag it with
`sylius_live_one_page_checkout.order_processor` (with an appropriate priority) so it's included in
the plugin's composite:

```yaml
services:
    App\OrderProcessing\MyCustomProcessor:
        tags:
            - { name: sylius.order_processor, priority: 15 }
            - { name: sylius_live_one_page_checkout.order_processor, priority: 15 }
```

Without this, the Live Component's `FormComponent` will compute totals/hash without your
processor's effect, while `OrderIntegrityChecker` (which still uses the full core composite) will
compute them with it - causing a false `state_mismatch` redirect on every checkout submission.

## License

Released under the [MIT License](LICENSE).
