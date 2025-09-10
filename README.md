<p align="center">
  <a href="https://ifthenpay.com/" target="_blank">
    <img src="./src/pix/ifthenpay_brand.svg" alt="ifthenpay" width="220" />
  </a>
</p>

<h1 align="center">⭐ ifthenpay – Moodle Payment Gateway ⭐</h1>

<p align="center">
  Developer guide for local development with <a href="https://code.visualstudio.com/docs/devcontainers/containers">VS Code Dev Containers</a> + <a href="https://www.docker.com/">Docker</a>.
</p>

<p align="center">
  <a href="https://code.visualstudio.com/docs/devcontainers/containers"><img alt="Dev Containers" src="https://img.shields.io/badge/VS%20Code-Dev%20Containers-007ACC?logo=visualstudiocode"></a>
  <a href="https://www.docker.com/"><img alt="Docker" src="https://img.shields.io/badge/Dockerized-🐳-2496ED?logo=docker"></a>
  <a href="https://www.php.net/releases/8.2/en.php"><img alt="PHP" src="https://img.shields.io/badge/PHP-%E2%89%A5%208.2-777BB4?logo=php"></a>
  <a href="https://moodle.org/"><img alt="Moodle" src="https://img.shields.io/badge/Moodle-Plugin-ff8f00?logo=moodle"></a>
  <a href="https://xdebug.org/"><img alt="Xdebug" src="https://img.shields.io/badge/Xdebug-3-2b9e4b"></a>
  <a href="https://getcomposer.org/"><img alt="Composer" src="https://img.shields.io/badge/Composer-Required-885630?logo=composer"></a>
  <a href="https://nodejs.org/en/blog/release/v20.0.0/"><img alt="Node" src="https://img.shields.io/badge/Node-20.x-339933?logo=node.js"></a>
  <a href="https://gruntjs.com/"><img alt="Grunt" src="https://img.shields.io/badge/Build-Grunt-FAA918?logo=grunt"></a>
  <a href="https://dev.mysql.com/doc/refman/8.0/en/"><img alt="MySQL" src="https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql"></a>
</p>

---

## 🎯 Overview

Ifthenpay payment gateway plugin for <a href="https://moodle.org/">Moodle</a> with a batteries‑included local dev setup (🐳 Docker + <a href="https://code.visualstudio.com/docs/devcontainers/containers">Dev Containers</a>). It provides reproducible environments, coding standards, AMD build tasks, and ready‑to‑use <a href="https://xdebug.org/">Xdebug</a> debugging.

---

## 🧰 Tech Stack

- **Runtime:** <a href="https://moodle.org/">Moodle</a> · <a href="https://www.php.net/">PHP</a> ≥ 8.2 · <a href="https://xdebug.org/">Xdebug</a> 3
- **Database:** <a href="https://dev.mysql.com/doc/refman/8.0/en/">MySQL 8.0</a>
- **Dev Environment:** <a href="https://code.visualstudio.com/docs/devcontainers/containers">VS Code Dev Containers</a> + <a href="https://docs.docker.com/compose/">docker-compose</a>
- **PHP Tooling:** <a href="https://getcomposer.org/">Composer</a> · <a href="https://github.com/squizlabs/PHP_CodeSniffer">PHPCS</a> (Moodle CS) · <a href="https://cs.symfony.com/">PHP-CS-Fixer</a> · <a href="https://phpstan.org/">PHPStan</a> · <a href="https://phpmd.org/">PHPMD</a>
- **JS/AMD:** <a href="https://nodejs.org/">Node</a> 20 + <a href="https://gruntjs.com/">Grunt</a> (uglify, watch)
- **JS QA:** <a href="https://eslint.org/">ESLint</a> (with <a href="https://github.com/gajus/eslint-plugin-jsdoc">JSDoc</a>, <a href="https://github.com/xjamundx/eslint-plugin-promise">Promise</a>, <a href="https://babel.dev/docs/eslint-plugin-babel">Babel</a>, <a href="https://eslint.org/docs/latest/use/configure/migration-guide">Globals</a>) · <a href="https://stylelint.io/">Stylelint</a> (+ <a href="https://github.com/stylelint-stylistic/stylelint-stylistic">Stylistic plugin</a> · <a href="https://github.com/stylelint/stylelint-config-standard">Config Standard</a>)

---

## ⚡ Quickstart

1. **Clone & open in VS Code**
   <em>Command Palette → “Dev Containers: Reopen in Container”.</em>
2. **First run** installs deps (`composer install`, `npm install`) and executes the post‑start hook to link the plugin into Moodle.
3. **Open Moodle:** <code>http://localhost:8080</code> (first run may show installer).
4. **Open Database:** <code>http://localhost:8081</code>.

---

## 🧱 Project Structure

```text
.
├─ .devcontainer/
│  ├─ devcontainer.json        # Compose integration, ports, extensions, post-commands
│  ├─ Dockerfile.app           # Moodle runtime (Bitnami) with Xdebug
│  ├─ Dockerfile.dev           # VS Code dev image (CLI tools)
│  └─ post-start.sh            # Symlink plugin into Moodle on shared volume
│
├─ .vscode/
│  ├─ launch.json              # Xdebug launchers (web + CLI)
│  └─ ifthenpay-moodle.code-workspace
│
├─ src/                        # Plugin source (PHP, templates, AMD JS under src/amd/*)
├─ vendor/                     # Composer dependencies
├─ composer.json               # PHP toolchain (PHPCS, PHP-CS-Fixer, PHPStan, PHPMD)
├─ package.json                # JS toolchain (Grunt, ESLint, Stylelint)
├─ Gruntfile.js                # AMD build/watch tasks
├─ phpcs.xml                   # Coding standards (Moodle CS)
├─ phpstan.neon                # Static analysis config
└─ docker-compose.yml          # App, DB, dev services, volumes/ports
```

> 🔗 **Symlink created on start**
>
> <code>/workspace/bitnami/moodle/payment/gateway/ifthenpay → /opt/dev/ifthenpay</code>
> Lives on the Bitnami shared volume; it may look “dangling” inside the dev container but is valid for the app.

---

## 🧩 Container Topology

- **Database:** <code>ifthenpay-db</code> – <a href="https://dev.mysql.com/doc/refman/8.0/en/">MySQL 8.0</a> (dev creds: <code>moodleuser:userpass</code>, DB: <code>moodle</code>).
- **App (Moodle):** Bitnami <a href="https://hub.docker.com/r/bitnami/moodle">Moodle 4.3</a> with <a href="https://xdebug.org/">Xdebug 3</a> enabled (dev creds: <code>admin:adminpass</code>).
- **Dev:** VS Code Dev Container (PHP 8.2 + Node 20 + <a href="https://gruntjs.com/">grunt-cli</a>).

**Volumes**

- <code>mysql_data</code> – MySQL data.
- <code>bitnami</code> – Moodle code/data; also where the plugin symlink lives.

---

## ✍️ Editor & Workspace Tips

- **Multi-root workspace**: the repo ships a workspace with two roots → _Plugin (repo)_ and _Moodle Core + Data (bitnami)_, so navigation & search cover both.
- **Intelephense indexing**: Moodle core paths are pre-added to <code>includePaths</code> so symbols resolve across roots.
- **Watch & search hygiene**: <code>moodledata</code>, <code>cache</code>, and <code>temp</code> are excluded from search/watch to keep VS Code snappy.
- **Xdebug mappings**: preconfigured in <code>.vscode/launch.json</code> (e.g., <code>/bitnami → /workspace/bitnami</code> and plugin → <code>src/</code>), so breakpoints bind without tweaks.

---

## 🛠️ Commands

**PHP (Composer)**

```bash
composer run lint      # PHPCS (Moodle CS)
composer run lint:fix  # PHPCBF + PHP-CS-Fixer
composer run analyse   # PHPStan
composer run qa        # Lint + Analyse
```

**JS / AMD (Grunt)**

```bash
npm run build         # Build AMD bundles (Grunt)
npm run watch         # Watch & rebuild AMD bundles

npm run lint:js       # Lint JavaScript (ESLint)
npm run lint:js:fix   # Auto-fix JavaScript issues
npm run lint:css      # Lint CSS (Stylelint)
npm run lint:css:fix  # Auto-fix CSS issues

npm run lint          # Run all linters
npm run lint:fix      # Auto-fix all fixable issues
```

---

## 🐞 Debugging (Xdebug)

1. VS Code → **Run and Debug** → **“Listen for Xdebug (Moodle web)”** (port `9003`).
2. Hit a page that executes your plugin code (e.g., settings/checkout) and breakpoints will bind.
3. CLI: **“Listen for Xdebug (CLI in dev)”**.

---

## ❓ Troubleshooting

- **Plugin not detected** → re-run: `bash .devcontainer/post-start.sh`
- **Breakpoints not hit** → use the correct listener and set a breakpoint early (e.g., `lib.php`).
- **Tools missing** → run `composer install` / `npm install` inside the container.

## 📁 Plugin Source (overview)

This plugin follows the official Moodle **plugin structure & coding guidelines** (see: [Moodle Plugins docs](https://moodledev.io/docs/guides), [Payment gateway API](https://moodledev.io/docs/apis/subsystems/payment)). Classes are PSR‑4 autoloaded under `paygw_ifthenpay\*`, UI is rendered with Mustache, and browser JS is shipped as **AMD modules** compiled via Grunt.

```text
src/
├─ amd/
│  ├─ src/
│  │  ├─ admin_gateway_form.js      # Enhancements for the admin gateway form
│  │  ├─ gateways_modal.js          # UI modal for method selection / UX helpers
│  │  └─ return.js                  # Return‑page UX (polling/spinner/retry)
│  └─ build/                        # Minified AMD bundles (committed for releases)
│
├─ classes/
│  ├─ adminsetting/
│  │  └─ backofficekey.php          # Custom admin setting + server‑side validation
│  └─ local/
│     ├─ api_client.php             # HTTP client for ifthenpay endpoints
│     ├─ data_formatter.php         # Small helpers (payloads/data types)
│     └─ gateway.php                # Gateway integration glue (Payment Account form/adapters)
│
├─ db/
│  ├─ install.xml                   # DB schema
│  └─ uninstall.php                 # Cleanup on uninstall
│
├─ lang/                            # Language strings
├─ pix/                             # Icons/assets
├─ templates/
│  └─ ifthenpay_button_placeholder.mustache  # UI partials
│
├─ cancel.php                       # Cancel/error landing
├─ lib.php                          # Business logic (helper functions)
├─ pay.php                          # Starts a payment attempt
├─ return.php                       # Handles return from provider (poll + update)
├─ settings.php                     # Admin settings (Plugin Core settings)
├─ styles.css                       # Small admin/UI styles
├─ version.php                      # Component metadata (version/reqs)
└─ webhook.php                      # Server‑to‑server notifications (callback endpoint)
```

> **Notes:** author AMD in `amd/src/*` and build to `amd/build/*` via `npm run build` (or `npm run watch`). Ensure built files are committed for distribution.

---

<h1 align="center">Happy Coding! 🚀</h1>
