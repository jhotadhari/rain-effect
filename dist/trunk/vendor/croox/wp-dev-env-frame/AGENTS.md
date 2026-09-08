# AGENTS.md

`croox/wp-dev-env-frame` — abstract WordPress framework library (Composer package) that generated plugins/themes/childthemes extend. PSR-4: `croox\wde\` → `src/`. No runtime deps, no build step.

## Ecosystem (read before changing anything)

Part of the `wp-dev-env` toolset; the three packages work hand in hand and are version-locked:

- `wp-dev-env-frame` (this repo) — PHP base classes + utils for generated projects.
- `generator-wp-dev-env` — Yeoman generator, scaffolds projects.
- `wp-dev-env-grunt` — grunt build env for generated projects.

Local checkouts on the author's machine (not guaranteed elsewhere): generator at `../../node/generator-wp-dev-env`, grunt at `../../node/wp-dev-env-grunt`.

Version chain: composer.json `version` here is pinned by the generator's `package.json` `subModules` and injected into generated projects (`wde` init arg + composer require). Release order: bump version here first, then update the generator's `subModules` and bump it (see its AGENTS.md). `Project::get_active_frame()` admin-warns when a consumer requires a different frame version than the one installed. Consumers receive new frame versions by regenerating their projects — see generator AGENTS.md.

## Verify

- No tests, lint, or CI exist. Verify with `php -l <file>`; `composer validate` for composer.json.
- Every PHP file has a `! defined( 'WPINC' )` guard and classes call WP functions in constructors — nothing runs outside WordPress.

## Architecture

- `src/Project.php` — abstract base; subclasses `Plugin`, `Theme`, `Childtheme`, `Childtheme_Enfold`, `Childtheme_Hello_Elementor`, `Theme_Twentynineteen`.
- `src/Block.php` — standalone abstract base for Gutenberg blocks (does not extend Project).
- `src/utils/*` — static helpers (`Arr::get` array-path accessor, `Wpml`, `Attachment`, …), namespace `croox\wde\utils`.

## Conventions that must be preserved

- Singletons via `Project::get_instance( $init_args )`; `$init_args['FILE_CONST']` must be the consuming file's abs path.
- Releases are commits that bump composer.json `version` (style: "Bump version X.Y.Z").
- `Project::_include( $key )` loads `inc/{$prefix}_include_{$key}.php` and then calls the function `{$prefix}_include_{$key}()` — that file must define it. Keys: `post_types_taxs`, `roles_capabilities`, `fun`; themes add `template_functions`, `template_tags`.
- Use magic `__get` properties (`->version`, `->prefix`, …); the `get_dir_url()`-style getters are deprecated (still error_log). `->dir_path` has trailing slash, `->dir_url`/`->dir_basename` none.
- Style: tabs, old-style `function __construct` (no visibility), no strict_types, match surrounding code over PSR-12.
