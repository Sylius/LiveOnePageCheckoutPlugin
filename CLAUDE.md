# CLAUDE.md

Guidance for Claude Code (claude.ai/code) when working in this repository.

## What this is

**Sylius Live One Page Checkout** - a Sylius 2.x plugin that replaces the default
multi-step checkout with a single-page checkout built on **Symfony UX Live Components**.
The whole cart, address, shipping, payment and summary UI lives on one page and updates
live (no full reloads) as the customer edits it.

The feature is **toggled per channel**: an admin enables "one page checkout" on a channel
and only that channel switches to the one-page flow; other channels keep the standard
Sylius checkout.

- Requires: PHP `^8.2`, `sylius/sylius` `^2.0`.
- Namespace: `Sylius\LiveOnePageCheckoutPlugin\` → `src/`.
- Tests namespace: `Tests\Sylius\LiveOnePageCheckoutPlugin\` → `tests/` + `tests/TestApplication/src/`.

## Development commands

The plugin runs against the bundled test application. Two equivalent setups are supported,
a plain local Symfony/PHP install, or Docker via the `Makefile`. Use whichever the contributor
has; the `Makefile` targets are thin wrappers around the `vendor/bin/*` commands.

| Task              | Local                                                        | Docker             |
|-------------------|-------------------------------------------------------------|--------------------|
| Install / boot    | see `README.md`                                             | `make init`        |
| DB + migrations   | `vendor/bin/console doctrine:migrations:migrate -n`         | `make database-init` |
| Fixtures          | `vendor/bin/console sylius:fixtures:load -n`                | `make load-fixtures` |
| PHPUnit           | `vendor/bin/phpunit`                                        | `make phpunit`     |
| Behat (non-JS)    | `vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"` | `make behat` |
| PHPStan (max)     | `vendor/bin/phpstan analyse -c phpstan.neon`               | `make phpstan`     |
| ECS               | `vendor/bin/ecs check`                                      | `make ecs`         |

PHPStan runs at **level max** over `src` and `tests` (see `phpstan.neon`). Behat JS scenarios
need a running server + headless Chrome, see `README.md` for the full recipe. For an ad-hoc
command inside the Docker container: `docker compose exec -T php <cmd>`.

## Architecture

The plugin is a set of decorators, compiler passes, a Live Component and Twig hooks layered
on top of core Sylius. Every piece gates its behaviour on the per-channel flag through
`CheckoutEnabledChecker`, so a disabled channel behaves exactly like stock Sylius.

### 1. Per-channel toggle
- `Model/ChannelOnePageCheckoutAwareTrait` + `...AwareInterface` add the boolean column
  `one_page_checkout_enabled` and its accessors. `Model/ChannelInterface` combines the Sylius
  `ChannelInterface` with the aware interface; the application's `Channel` entity must
  implement it and use the trait (see Installation in `README.md`).
- `Form/Extension/Admin/ChannelTypeExtension` adds the admin checkbox to the channel form.
- `Checker/CheckoutEnabledChecker` reads the flag. **This is the central gate**, nearly
  every other service depends on `CheckoutEnabledCheckerInterface`.
- `Migrations/Version20260629120000` (MySQL) and `...0001` (PostgreSQL) add the column.

### 2. Routing into the one-page flow
- Route `sylius_live_one_page_checkout_shop_checkout` → `/{_locale}/checkout/one-page`
  (`config/routes/shop.yaml`). It reuses `sylius.controller.order::updateAction` with
  `CheckoutType`, the `sylius_order_checkout` state machine `complete` transition, and the
  `one_page.html.twig` template; on success it redirects to `sylius_shop_order_pay`.
- `Checkout/ChannelAwareCheckoutResolver` **decorates** `sylius.resolver.checkout`. On each
  main request, if the channel is enabled and the request matches a standard multi-step
  checkout path (regex excludes `/checkout/one-page`), it redirects to the one-page route.
  Otherwise it delegates to the decorated resolver.

### 3. The Live Component
- `Twig/Component/Checkout/FormComponent` (`#[AsLiveComponent]`, key
  `sylius_live_one_page_checkout:checkout:form`) drives the page. It uses Sylius'
  `ResourceFormComponentTrait<OrderInterface>`. On mount and before every re-render it runs
  the order processor and recomputes `stateHash` (see integrity, below).
- `#[LiveAction]`s: `removeCoupon`, `toggleNewShippingAddress`, `toggleNewBillingAddress`,
  `toggleDifferentBillingAddress`. `instantiateForm()` guarantees the order has shipping and
  billing `Address` objects so guest shipping-method filtering by country works.

### 4. Direct `cart → completed` checkout transition
Standard Sylius checkout walks cart → addressed → shipping/payment selected → completed.
One-page collects everything at once, so the plugin adds a single `complete` transition
straight from `cart` to `completed`, guarded so it only fires on enabled channels. It
supports **both** state-machine adapters Sylius can use:
- **Symfony Workflow**: `Compiler/AddOrderCheckoutWorkflowTransitionPass` injects the
  transition into the workflow definition; `EventListener/Workflow/CheckoutCompletionGuard`
  blocks `complete`-from-`cart` when the channel is disabled.
- **winzou state machine** (only if `winzouStateMachineBundle` is installed): the transition
  and guard live in `config/integrations/winzou_state_machine/`, wired by
  `WinzouStateMachine/CheckoutCompletionGuard`. Loaded conditionally by `config/integrations.php`.

### 5. Order integrity / anti-tamper (state hash)
Because totals can change (price, promotion, shipping) between render and submit, the plugin
verifies the order the customer saw still matches on submit.
- `Provider/OrderHashDataProvider` builds a canonical array (total + sorted promotion,
  shipping-method and payment-method codes); `Hasher/OrderHasher` md5s it.
- The hash is rendered into the form as `stateHash`; on submit `Provider/SubmittedHashProvider`
  reads it back from the request.
- `EventListener/OrderIntegrityChecker` **decorates** Sylius' order integrity checker. On
  enabled channels it re-processes the order, compares hashes via
  `Checker/OrderConsistencyChecker`, and on mismatch stops the event, flashes
  `state_mismatch` and redirects back to the one-page route. Disabled channels fall through
  to the decorated checker.

### 6. Order processing
- `RegisterOnePageOrderProcessorsPass` builds a plugin-owned composite order processor
  (`...order_processor.composite`) from services tagged `sylius_live_one_page_checkout.order_processor`.
- `AddOrderProcessorTagPass` re-tags the relevant **core** Sylius processors (adjustments
  clearer, prices recalculator, shipping charges, promotion, taxes, checkout payment) into
  that tag with explicit priorities. The Live Component uses this composite to recalculate
  the order live without persisting real checkout steps.

### 7. Pre-filled shipping address
`Cart/Context/DefaultShippingAddressCartContext` decorates the cart context: on enabled
channels, if the customer has a default address, it clones it onto the cart's shipping
address so the form starts pre-filled.

### 8. Twig hooks & templates
The UI is entirely hook-driven. `config/twig_hooks/**` maps the one-page tree
(`header`, `content`, `form`, and the `sections`: `shipping_method`, `address`,
`payment_method`, `summary`), plus the admin channel checkbox and shared address partials.
`templates/` mirrors those hook names one-to-one, to change a fragment, find the matching
hook in `config/twig_hooks/` and edit its template.

### Config loading
`SyliusLiveOnePageCheckoutExtension` loads `config/services.php`, which imports
`config/services/**` (one file per concern: `checker`, `hasher`, `provider`, `resolver`,
`form`, `listener`, `cart`, `validator`, `twig_component`). `config/config.yaml` imports the
twig hooks and `integrations.php`. Migrations are prepended via `PrependDoctrineMigrationsTrait`.

## Conventions
- Service IDs and config keys are prefixed `sylius_live_one_page_checkout.` /
  `sylius_live_one_page_checkout_`.
- Optional third-party integrations (e.g. winzou) live under `config/integrations/` and load
  conditionally, never hard-require them.
- Do not add code comments unless asked.
- Run `make ecs` and `make phpstan` (level max) before considering a change done.

## Companion docs
- `README.md`, install & wiring the plugin into a Sylius app, plus running it locally.
