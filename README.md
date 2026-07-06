<p align="center">
    <a href="https://sylius.com" target="_blank">
        <picture>
          <source media="(prefers-color-scheme: dark)" srcset="https://media.sylius.com/sylius-logo-800-dark.png">
          <source media="(prefers-color-scheme: light)" srcset="https://media.sylius.com/sylius-logo-800.png">
          <img alt="Sylius Logo." src="https://media.sylius.com/sylius-logo-800.png">
        </picture>
    </a>
</p>

<h1 align="center">Live One Page Checkout</h1>

<p align="center">A Sylius 2.x plugin that replaces the multi-step checkout with a single,
live-updating one page checkout, enabled per channel.</p>

## What it does

- Puts cart, addresses, shipping, payment and order summary on **one page**, updating live
  via **Symfony UX Live Components** — no full page reloads as the customer fills the form.
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

## Installation

1. Require the plugin:

   ```bash
   composer require sylius/live-one-page-checkout-plugin
   ```

2. Register the bundle in `config/bundles.php`:

   ```php
   Sylius\LiveOnePageCheckoutPlugin\SyliusLiveOnePageCheckoutPlugin::class => ['all' => true],
   ```

3. Import the plugin config (e.g. `config/packages/sylius_live_one_page_checkout.yaml`):

   ```yaml
   imports:
       - { resource: "@SyliusLiveOnePageCheckoutPlugin/config/config.yaml" }
   ```

4. Make your `Channel` entity aware of the flag — implement the plugin interface and use its
   trait:

   ```php
   use Sylius\Component\Core\Model\Channel as BaseChannel;
   use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelInterface;
   use Sylius\LiveOnePageCheckoutPlugin\Entity\ChannelOnePageCheckoutAwareTrait;

   class Channel extends BaseChannel implements ChannelInterface
   {
       use ChannelOnePageCheckoutAwareTrait;
   }
   ```

   and point the Sylius channel resource at it:

   ```yaml
   sylius_channel:
       resources:
           channel:
               classes:
                   model: App\Entity\Channel\Channel
   ```

5. Apply the migration adding the `one_page_checkout_enabled` column:

   ```bash
   bin/console doctrine:migrations:migrate
   ```

6. In the admin panel, edit a channel and tick **Enable one page checkout**.

## Local development

This repository ships a **test application** (`sylius/test-application`) so the plugin can be
run and tested in isolation. Pick either a local Symfony/PHP setup or Docker.

### Traditional (local PHP)

```bash
(cd vendor/sylius/test-application && yarn install && yarn build)
vendor/bin/console assets:install

vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n
vendor/bin/console sylius:fixtures:load -n   # optional demo data

symfony server:start -d
```

Configure database credentials in `tests/TestApplication/.env` and
`tests/TestApplication/.env.test`. The app is then served by the Symfony CLI (default
`https://localhost:8000`).

### Docker

```bash
make init            # install deps, build assets, start containers
make database-init   # create DB + run migrations
make load-fixtures   # optional demo data
```

The app is then available at `http://localhost`.

### Quality & tests

| Task           | Local                                                       | Docker         |
|----------------|-------------------------------------------------------------|----------------|
| PHPUnit        | `vendor/bin/phpunit`                                        | `make phpunit` |
| Behat (non-JS) | `vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"` | `make behat` |
| PHPStan (max)  | `vendor/bin/phpstan analyse -c phpstan.neon`                | `make phpstan` |
| ECS            | `vendor/bin/ecs check`                                      | `make ecs`     |

### Behat JS scenarios

JS scenarios need a running server and headless Chrome:

```bash
symfony server:ca:install
APP_ENV=test symfony server:start --port=8080 --daemon

google-chrome-stable --enable-automation --disable-background-networking --no-first-run \
  --headless --remote-debugging-port=9222 --window-size=2880,1800 \
  --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1

vendor/bin/behat --strict --tags="@javascript,@mink:chromedriver"
```

Non-JS scenarios:

```bash
vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
```

## License

Released under the [MIT License](LICENSE).
