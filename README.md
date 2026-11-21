
# README Viewer for Cotonti

A plugin for CMF Cotonti v1.0 (Verona) and v0.9.x (Siena) that adds a "Documentation" button to the details page of any extension — module or plugin — in the admin panel. The button opens the extension's README file in the browser with rights checking, size limits, HTTPS policy, and content sanitization.

Document version: 1.3.3. Date: 2026-11-21.

---  
<img width="1920" height="1080" alt="readme_viewer_001" src="https://github.com/user-attachments/assets/acba12ec-6312-423d-b8cb-e2eadabfc66a" />
<img width="1920" height="1080" alt="readme_viewer_002" src="https://github.com/user-attachments/assets/c4acd659-b7ee-472d-b03f-91b77ca31734" />
<img width="1920" height="1080" alt="readme_viewer_003" src="https://github.com/user-attachments/assets/282be19b-ab5c-4fae-8b64-eb19a148e02e" />
<img width="1920" height="1080" alt="readme_viewer_004" src="https://github.com/user-attachments/assets/c16238ba-36d6-41c9-94cc-90cd64b78b36" />
<img width="1012" height="268" alt="readme_viewer_005" src="https://github.com/user-attachments/assets/f477895b-2b6c-47a6-ad8a-6ccc4d32b8a2" />


---


## Table of Contents

1. [Introduction](#introduction-en)
2. [Why keeping documentation at hand matters](#why-docs-en)
3. [Plugin features](#features-en)
4. [Requirements](#requirements-en)
5. [Installation](#installation-en)
6. [Plugin file structure](#file-structure-en)
7. [Overall business logic](#business-logic-en)
8. [Routing and URL](#url-routing-en)
9. [README search algorithm](#search-algorithm-en)
10. [Supported document formats](#formats-en)
11. [Plugin settings](#configuration-en)
12. [Function reference](#functions-en)
13. [Markdown converter](#markdown-en)
14. [HTML sanitizer](#sanitizer-en)
15. [Anchor and heading handling](#anchors-en)
16. [Security](#security-en)
17. [View page template tags](#template-tags-en)
18. [Admin panel integration](#admin-en)
19. [Localization](#localization-en)
20. [Usage examples](#examples-en)
21. [Debugging and diagnostics](#debugging-en)
22. [Troubleshooting](#troubleshooting-en)
23. [FAQ](#faq-en)
24. [Plugin removal](#removal-en)
25. [License](#license-en)

---

<a id="introduction-en"></a>
## 1. Introduction

README Viewer is a plugin for CMF Cotonti that solves a narrow but important administrative task: giving the site administrator fast and safe access to the documentation of any installed extension directly from the admin panel — without switching to FTP, file managers, or searching for the required file through server directories.

The plugin adds a "Documentation" button to the extension details page (where the administrator sees the version, author, dependencies, and control buttons). Clicking it opens a page in a new tab that displays the content of the extension's README file — with proper typography, table of contents support, working internal links, and full compliance with the site's security policies.

Technically, the plugin is a classic Cotonti extension consisting of:

- A standalone entry point (`readme_viewer.php`) that handles requests to `index.php?e=readme_viewer`.
- A hook `admin.extensions.details` that adds a button to the extension details page.
- A set of functions for finding and safely reading README files (`inc/readme_viewer.functions.php`).
- A view-mode executor (`inc/readme_viewer.view.php`) that produces the page HTML.
- A Markdown converter and HTML sanitizer (`inc/readme_viewer.markdown.php`).
- An embedded Parsedown library (`lib/Parsedown.php`) for Markdown rendering.
- An XTemplate template (`tpl/readme_viewer.view.tpl`).
- Language files (`lang/readme_viewer.ru.lang.php`, `lang/readme_viewer.en.lang.php`).

The plugin does not modify Cotonti core files. The only edit needed to make the button appear is a one-time insertion of a fragment into the `admin.extensions.tpl` template (preferably via a theme override). All file search and read operations are isolated from direct file system access through strict path control.

<a id="why-docs-en"></a>
## 2. Why keeping documentation at hand matters

### 2.1. Administrator efficiency

When an administrator installs, updates, or debugs an extension, they constantly need information: what a specific configuration parameter is called, what a particular setting does, how to correctly format records, what permissions to assign, how to configure the category structure. If all this information lives in the extension's README file — but access requires switching to an FTP client, locating the folder, downloading the file, and opening it locally — each such operation takes from thirty seconds to several minutes.

The plugin reduces this to a single click.

### 2.2. Reduced error risk

Without quick access to documentation, the administrator often acts on intuition or memory. This increases the risk of misconfiguration: enabling the wrong option, setting incompatible permissions, forgetting a dependency on another extension. The built-in "Documentation" button makes the correct path (read the README) shorter than the path "try it blind and see what happens."

### 2.3. Single point of documentation storage

The plugin supports two independent channels for storing README files:

- **Local** — a `_dir_readme_viewer` folder inside each extension. Here the extension developer (or the site administrator manually) places the current README.
- **Centralized** — a `_dir_readme_viewer_list` folder inside the plugin itself. Here you can collect documentation for all installed extensions in one place, following a simple naming contract.

This lets the administrator choose a management model: update READMEs alongside extension updates, or keep all documentation in one directory for quick access.

### 2.4. Security without compromise

A naive implementation of "show a file from the extension folder" creates a threat: if the administrator mistypes a URL or deliberately tries to access a file outside the allowed directory, the application may expose the content of system files. The plugin protects against this at every level:

- URL parameter validation (only `type` from the whitelist `module`/`plug`, only `code` matching a strict regex).
- `realpath()`-based path resolution with a mandatory check that the real file path is strictly inside the base directory.
- Extension whitelist (`.md`, `.html`, `.txt`).
- HTML sanitization via `DOMDocument` with a whitelist of tags and attributes.
- Markdown via Parsedown in safe mode followed by DOMDocument sanitization.
- File size limit from configuration.
- HTTPS policy with strict or soft mode.
- Standard permissions check via the Cotonti ACL.

### 2.5. Consistent documentation appearance

The plugin brings all READMEs to a single visual style: same headings, same code blocks, same typography for inline code, tables, lists. This reduces visual noise and makes documentation predictably readable regardless of how neatly the source Markdown file was formatted.

<a id="features-en"></a>
## 3. Plugin features

### 3.1. "Documentation" button in the admin panel

On the details page of any installed extension:

- `admin.php?m=extensions&a=details&mod=page` — for a module
- `admin.php?m=extensions&a=details&pl=contact` — for a plugin

a "Documentation" button appears. It opens in a new tab (`target="_blank"`) and is protected from tab-nabbing (`rel="noopener noreferrer"`). The button is shown only if a README file is actually found.

### 3.2. Two search priorities

The plugin searches for the file in two places:

1. First — inside the extension folder itself, in the `_dir_readme_viewer` subdirectory.
2. If not found — in the plugin's centralized library `_dir_readme_viewer_list`.

The priorities are strictly fixed: if a file is found at the first level, the second level is not checked. This gives the administrator control: the local README always "overrides" the centralized one.

### 3.3. Three document formats

Supported:

- `.md` — Markdown, rendered into HTML via embedded Parsedown (with subsequent sanitization), or via an embedded fallback parser if Parsedown is unavailable.
- `.html` — HTML, passed through a DOMDocument sanitizer with a whitelist, or shown as source code (configurable).
- `.txt` — plain text, shown in `<pre>` with full escaping.

The format is determined by the real file extension on disk, not by the URL.

### 3.4. Working anchors in documentation

The plugin automatically:

- Generates `id` for every `<h1>`–`<h6>` heading if it doesn't already have one.
- Resolves id collisions: on duplicate, `-2`, `-3`, and so on is appended to the id.
- Absolutizes relative anchors `href="#anchor"` — converts them into absolute URLs of the form `https://site.tld/index.php?e=readme_viewer&m=view&type=module&code=page#anchor`.

The latter is critical: Cotonti inserts `<base href="...">` into the theme header, which causes the browser to resolve relative anchors not against the current page URL but against the base. Without absolutization, clicking a table of contents item would lead the user to a foreign page without plugin parameters.

### 3.5. HTTPS policy

The plugin distinguishes between secure (HTTPS) and insecure (HTTP) connections. There are two modes:

- Soft: with HTTP a warning is shown, but the file content is still displayed. Convenient for local development.
- Strict: with HTTP the file is not read at all, only the warning is displayed.

The hosts `localhost` and `127.0.0.1` are always treated as secure regardless of mode.

### 3.6. File size limit

The `rv_max_size` setting defines the README size limit in bytes. On exceed, the file is not read and the "too large" message is displayed. Protects against accidentally loading a gigabyte file with a `.md` extension and crashing PHP on memory.

Available values in the UI: 1 MiB, 2 MiB, 3 MiB, 5 MiB.

### 3.7. Standard ACL

The plugin is protected by the standard Cotonti permission system. By default only superadmins have access; permissions for other groups are assigned via Admin → Rights.

### 3.8. Localization

The plugin ships with Russian and English localization. All strings are externalized into language files. Localization follows the standard Cotonti mechanism: `cot_langfile()` automatically picks the file by `Cot::$usr['lang']` with fallback to `en`.

<a id="requirements-en"></a>
## 4. Requirements

- **Cotonti** v1.0 (Verona) or v0.9.x (Siena).
- **PHP** 7.3 or higher (compatible with PHP 8.x).
- PHP extensions: `mbstring`, `dom` (bundled with standard PHP builds since 5.x), `json`.
- Superadmin rights for installation.
- FTP access or a file manager for placing README files in extension folders.

The plugin does not require additional Composer dependencies: Parsedown is bundled with the distribution.

<a id="installation-en"></a>
## 5. Installation

### 5.1. Uploading files

Copy the entire `readme_viewer` folder to the `plugins/` directory of the Cotonti installation.

### 5.2. Installation via the admin panel

1. Sign in to the admin panel as a superadmin.
2. Open **Extensions**.
3. In the "Plugins" block, find "README Viewer".
4. Click **"Install"**.
5. Make sure all plugin parts are active.

### 5.3. Configuration

**Extensions → README Viewer → Settings**. Defaults are suitable for production but can be adjusted.

### 5.4. Adding the button to the template

The plugin itself assigns all required tags, but the `admin.extensions.tpl` template in the core doesn't contain a block for the button. A one-time template edit is needed.

Details — section [Admin panel integration](#admin-en).

### 5.5. Placing README files

For each extension you want to have the "Documentation" button for, create one of the two structures manually:

Option 1 (priority):

```
modules/<MODULE_CODE>/_dir_readme_viewer/README.md
plugins/<PLUGIN_CODE>/_dir_readme_viewer/README.md
```

Option 2 (centralized):

```
plugins/readme_viewer/_dir_readme_viewer_list/<CODE>_README.md
```

Details — sections [Search algorithm](#search-algorithm-en) and [Examples](#examples-en).

<a id="file-structure-en"></a>
## 6. Plugin file structure

```
plugins/readme_viewer/
├── readme_viewer.setup.php
├── readme_viewer.php
├── readme_viewer.admin.extensions.details.php
├── inc/
│   ├── readme_viewer.functions.php
│   ├── readme_viewer.view.php
│   └── readme_viewer.markdown.php
├── lib/
│   └── Parsedown.php
├── tpl/
│   └── readme_viewer.view.tpl
├── lang/
│   ├── readme_viewer.ru.lang.php
│   └── readme_viewer.en.lang.php
└── _dir_readme_viewer_list/
    ├── .htaccess
    └── index.html
```

### 6.1. `readme_viewer.setup.php`

Plugin metadata read by the core through `cot_infoget()` during installation (`system/extensions.php` → `cot_extension_install()`).

Contains:

- Block `[BEGIN_COT_EXT]` with metadata: `Code`, `Name`, `Description`, `Version`, `Date`, `Author`, `Copyright`, `Notes`, `Auth_guests`, `Lock_guests`, `Auth_members`, `Lock_members`, `Requires_modules`, `Requires_plugins`, `Recommends_modules`, `Recommends_plugins`.
- Block `[BEGIN_COT_EXT_CONFIG]` with settings: `rv_enforce_https`, `rv_html_mode`, `rv_max_size`.

Auth/lock values:

```
Auth_guests=0
Lock_guests=RW12345A
Auth_members=0
Lock_members=RW12345A
```

This means: at install time no group except superadmins has access. `cot_extension_install()` will automatically grant full rights (255 bits) only to `COT_GROUP_SUPERADMINS`. Other groups receive rights via Admin → Rights.

### 6.2. `readme_viewer.php`

Standalone entry point. Activated via hook `Hooks=standalone`. The core loads this file on requests to `index.php?e=readme_viewer` (see `system/plugin.php`).

The file does:

1. Sets `Cot::$env['location'] = 'readme_viewer'`.
2. Includes `inc/readme_viewer.functions.php` via `cot_incfile`.
3. Includes the language file via `cot_langfile`.
4. Checks ACL via `cot_auth('plug', 'readme_viewer')` and `cot_block($usr['auth_read'])`.
5. Parses the `m` parameter (default `view`, any other value is reduced to `view`).
6. Includes the mode executor — `inc/readme_viewer.view.php`.

### 6.3. `readme_viewer.admin.extensions.details.php`

Hook `admin.extensions.details`. Fires in `system/admin/admin.extensions.php` inside `case 'details'`, immediately before `$t->parse('MAIN.DETAILS')`.

In the hook scope:

- `$code` — extension code.
- `$is_module` — type (true for module).
- `$type` — 'module' or 'plug'.
- `$t` — XTemplate of the admin page.

The hook:

1. Checks the presence of `$code` and `$is_module`.
2. Includes `inc/readme_viewer.functions.php` and the language file.
3. Calls `rv_build_documentation_url($code, (bool) $is_module)`. If README isn't found, the function returns `null`.
4. Assigns template tags:

```
ADMIN_EXTENSIONS_HAS_DOC   — bool, whether README exists
ADMIN_EXTENSIONS_DOC_URL   — view page URL string or ''
ADMIN_EXTENSIONS_DOC_TITLE — button tooltip (title)
ADMIN_EXTENSIONS_DOC_LABEL — button text
```

Title and label values come from the language file (`Cot::$L['rv_open_doc']`, `Cot::$L['rv_open_doc_short']`).

### 6.4. `inc/readme_viewer.functions.php`

Public plugin functions for finding and reading README files. The file is loaded automatically through `system/plugin.php` (which does `cot_incfile($extensionCode, 'plug', 'functions')`), and explicitly from `readme_viewer.php` and `readme_viewer.admin.extensions.details.php`.

Detailed function descriptions — section [Function reference](#functions-en).

### 6.5. `inc/readme_viewer.view.php`

View-mode executor. Included from `readme_viewer.php`.

Tasks:

1. Validate URL parameters `type` and `code`.
2. Apply HTTPS policy.
3. Read the file via `rv_read_documentation()`.
4. Render into XTemplate.
5. Build `$pluginBody`, which `system/plugin.php` inserts into the standard container.

**Important:** the XTemplate variable is named `$rvTpl`, not `$t`. This is essential because `system/plugin.php` ends with:

```php
$pluginTemplate = $t ?? null;
require_once Cot::$cfg['system_dir'] . '/header.php';
$t = $pluginTemplate;
if (isset($t) && is_object($t)) { $t->parse('MAIN'); $t->out('MAIN'); }
else { echo $pluginBody; }
```

If the XTemplate were named `$t`, the core would capture it after `text('MAIN')` (which clears accumulated block data), then re-invoke `parse`/`out` — and the browser would show an empty block. Therefore using `$rvTpl` is not cosmetic, but a critical detail.

### 6.6. `inc/readme_viewer.markdown.php`

Markdown → HTML converter and HTML sanitizer. Included from `inc/readme_viewer.view.php` via `cot_incfile('readme_viewer', 'plug', 'markdown')`.

Contains:

- `rv_markdown_to_html()` — Markdown rendering.
- `rv_sanitize_html()` — HTML sanitization.
- `rv_absolutize_anchors()` — anchor absolutization.
- `rv_slugify()` — slug generation from text.
- `rv_add_heading_ids()` — adding ids to headings.

Detailed descriptions — sections [Markdown converter](#markdown-en), [HTML sanitizer](#sanitizer-en), [Anchor handling](#anchors-en).

### 6.7. `lib/Parsedown.php`

Bundled Parsedown library version 1.8.0. Used in `rv_markdown_to_html()`. Included directly from the plugin folder:

```php
require_once Cot::$cfg['plugins_dir'] . '/readme_viewer/lib/Parsedown.php';
```

If the file is absent or cannot be loaded for some reason, an embedded fallback parser inside `rv_markdown_to_html()` is used.

### 6.8. `tpl/readme_viewer.view.tpl`

XTemplate of the view page. Contains:

- `<style>` with page CSS.
- Block `MAIN` with nested blocks `RV_INSECURE`, `RV_OK`, `RV_ERROR`, `RV_NOT_FOUND`.
- Debug block for `cotcp` (appears only if cotcp is active and `tplpathenableview` mode is enabled).

Template tags — section [Template tags](#template-tags-en).

### 6.9. `lang/readme_viewer.ru.lang.php` and `lang/readme_viewer.en.lang.php`

Language files. Contain view page strings and admin metadata.

### 6.10. `_dir_readme_viewer_list/`

Centralized README file library. The `.htaccess` file denies directory listing and direct access to executable files. The `index.html` file is a stub against directory listing in case `.htaccess` is ignored by the web server.

<a id="business-logic-en"></a>
## 7. Overall business logic

The plugin implements two independent scenarios:

### 7.1. Scenario "Button in admin panel"

1. Administrator opens the extension details page: `admin.php?m=extensions&a=details&mod=page`.
2. Cotonti core in `system/admin/admin.extensions.php` builds the page and, before parsing the DETAILS block, includes all `admin.extensions.details` hooks.
3. Our hook (`readme_viewer.admin.extensions.details.php`) is invoked in this context.
4. The hook calls `rv_build_documentation_url($code, (bool) $is_module)`.
5. This function validates the extension code, searches for README via `rv_find_documentation()`. If a file is found, it builds the view page URL.
6. The hook assigns template tags (`ADMIN_EXTENSIONS_HAS_DOC`, `ADMIN_EXTENSIONS_DOC_URL`, `ADMIN_EXTENSIONS_DOC_TITLE`, `ADMIN_EXTENSIONS_DOC_LABEL`).
7. In the template (after the one-time edit), the `<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->` block renders the button.

### 7.2. Scenario "View documentation"

1. User clicks the "Documentation" button → the browser opens a URL like `index.php?e=readme_viewer&m=view&type=module&code=page`.
2. Cotonti core in `index.php` → `system/plugin.php` loads `readme_viewer.php`.
3. `readme_viewer.php`:
   - Sets location.
   - Includes functions and language strings.
   - Checks ACL via `cot_auth('plug', 'readme_viewer')` and `cot_block()`.
   - Parses `m`, always receives `view`.
   - Includes `inc/readme_viewer.view.php`.
4. `inc/readme_viewer.view.php`:
   - Parses `type` and `code` via `cot_import('...', 'G', 'ALP')`.
   - Builds `$rvViewUrl` — absolute URL of the current page for anchor absolutization.
   - Applies HTTPS policy.
   - Validates `code` via `rv_is_valid_code()`.
   - Calls `rv_read_documentation()`.
   - Depending on file extension, applies different rendering strategies.
   - Assigns template tags (`RV_TITLE`, `RV_CONTENT`, `RV_FILE_PATH`, `RV_FILE_NAME`, `RV_FILE_EXT`, `RV_ERROR_TEXT`, `RV_INSECURE_WARNING`).
   - Builds `$pluginBody`.
5. `system/plugin.php` inserts `$pluginBody` into the standard theme container.

<a id="url-routing-en"></a>
## 8. Routing and URL

### 8.1. URL format

```
index.php?e=readme_viewer&m=view&type=<module|plug>&code=<EXTENSION_CODE>
```

### 8.2. Parameters

| Parameter | Allowed values | Description |
|-----------|----------------|-------------|
| `e` | `readme_viewer` | Plugin code. Hard-coded by the core. |
| `m` | `view` | Mode. Any other value is forced to `view`. |
| `type` | `module` \| `plug` | Extension type. If `module` — search in `modules_dir`, otherwise `plugins_dir`. |
| `code` | `<code>` | Extension code. Validated by regex `^[a-z][a-z0-9_\-]*$`, up to 64 chars. |

### 8.3. Examples

Documentation for the `page` module:
```
index.php?e=readme_viewer&m=view&type=module&code=page
```

Documentation for the `contact` plugin:
```
index.php?e=readme_viewer&m=view&type=plug&code=contact
```

### 8.4. What happens with unknown values

- `type` is neither `module` nor `plug` → treated as `plug` (default).
- `code` contains invalid characters or is empty → the page displays "documentation not found".
- `m` is not `view` → forced to `view`.

<a id="search-algorithm-en"></a>
## 9. README search algorithm

### 9.1. Priorities

The file is searched in two places, in priority order:

**Priority 1** — the extension folder itself:

```
modules/<CODE>/_dir_readme_viewer/README.md
modules/<CODE>/_dir_readme_viewer/README.html
modules/<CODE>/_dir_readme_viewer/README.txt
modules/<CODE>/_dir_readme_viewer/readme.md
modules/<CODE>/_dir_readme_viewer/readme.html
modules/<CODE>/_dir_readme_viewer/readme.txt

plugins/<CODE>/_dir_readme_viewer/README.md
plugins/<CODE>/_dir_readme_viewer/README.html
plugins/<CODE>/_dir_readme_viewer/README.txt
plugins/<CODE>/_dir_readme_viewer/readme.md
plugins/<CODE>/_dir_readme_viewer/readme.html
plugins/<CODE>/_dir_readme_viewer/readme.txt
```

**Priority 2** — the plugin's centralized library:

```
plugins/readme_viewer/_dir_readme_viewer_list/<CODE>_README.md
plugins/readme_viewer/_dir_readme_viewer_list/<CODE>_README.html
plugins/readme_viewer/_dir_readme_viewer_list/<CODE>_README.txt
plugins/readme_viewer/_dir_readme_viewer_list/<CODE>_readme.md
plugins/readme_viewer/_dir_readme_viewer_list/<CODE>_readme.html
plugins/readme_viewer/_dir_readme_viewer_list/<CODE>_readme.txt
```

### 9.2. Order within a single level

The check goes in a strictly defined order:

1. First `README` (all capitals), then `readme` (all lowercase).
2. For each name — first `.md`, then `.html`, then `.txt`.

So for priority 1 the order is:

1. `README.md`
2. `README.html`
3. `README.txt`
4. `readme.md`
5. `readme.html`
6. `readme.txt`

The first file found is returned. The rest are not checked.

### 9.3. What is ignored

- `ReadMe.md`, `Readme.MD`, `README.MD` — ignored due to wrong case.
- `README2.md`, `readme_old.md` — ignored because the name is neither `README` nor `readme`.
- `README.php`, `README.sh`, `README.json` — ignored because the extension is outside the whitelist.
- Symlinks pointing outside `_dir_readme_viewer` — ignored due to the `realpath` check.

### 9.4. Implementation

The algorithm is implemented in two functions:

- `rv_find_documentation(?string $code, bool $isModule): ?array` — orchestrator, checks the extension folder and the library.
- `rv_probe_dir(string $dir, array $baseNames): ?array` — iterates over names and extensions in a specific directory, applying protection against path traversal and symlink-escape.

Details — section [Function reference](#functions-en).

<a id="formats-en"></a>
## 10. Supported document formats

### 10.1. Markdown (`.md`)

Rendered via embedded Parsedown. Settings:

- `setSafeMode(false)` — safe mode is disabled because with `setSafeMode(true)` Parsedown blocks any inline HTML in Markdown, and `<a id="anchor"></a>` would turn into text. Safety is enforced afterwards by the DOMDocument sanitizer.
- `setMarkupEscaped(false)` — safe inline HTML from the source is allowed (e.g., `<a id="anchor"></a>` for explicit anchors, `<span>`, `<div>`).
- `setBreaksEnabled(false)` — a single newline inside a paragraph doesn't become `<br>`.

After rendering, the HTML goes through `rv_sanitize_html()`. This DOMDocument sanitizer with a tag and attribute whitelist blocks `<script>`, `<iframe>`, `<object>`, `<embed>`, `on*` attributes, `javascript:` and `data:` in links, and passes safe tags from the whitelist.

Then the following transformations are applied:

1. `rv_add_heading_ids()` — adding `id` to headings.
2. `rv_absolutize_anchors()` — absolutizing relative anchors.

If Parsedown is unavailable, an embedded fallback parser is used (see section [Markdown converter](#markdown-en)).

### 10.2. HTML (`.html`)

Behavior is determined by the `rv_html_mode` setting:

- **`sanitize`** (default) — file goes through the DOMDocument sanitizer with a tag and attribute whitelist. Everything not in the list is removed entirely.
- **`plain`** — file is displayed as source code in `<pre>` with full escaping. Safe but not rendered.

**Tag whitelist:**

```
p, br, hr, h1, h2, h3, h4, h5, h6,
ul, ol, li, pre, code, blockquote,
strong, b, em, i, u, s, sub, sup,
a, img, table, thead, tbody, tr, td, th, caption,
span, div
```

**Attribute whitelist:**

- On any tag: `class`, `id`.
- On `<a>`: `href`, `title`, `rel`, `target`.
- On `<img>`: `src`, `alt`, `title`, `width`, `height`.

**Validation of `href` and `src`:**

Only values starting with are allowed:

- `http://`
- `https://`
- `/` (root-relative)
- `#` (anchors)
- `mailto:`

Everything else (including `javascript:`, `data:`, `ftp://`) — is removed.

### 10.3. Text (`.txt`)

Displayed in `<pre>` with class `rv-pre` and full escaping via `htmlspecialchars()`. Tags and any `<`, `>`, `&` characters are shown as-is. The only format with neither sanitization nor rendering — always safe by definition.

<a id="configuration-en"></a>
## 11. Plugin settings

Available via **Extensions → README Viewer → Settings**. Defined in `readme_viewer.setup.php` in the `[BEGIN_COT_EXT_CONFIG]` block:

```
rv_enforce_https=00:radio:0:1:1:
rv_html_mode=01:select:sanitize,plain:
rv_max_size=02:select:1048576,2097152,3145728,5242880:
```

### 11.1. `rv_enforce_https`

Type: radio (0 or 1).
Default value: `1`.

- `0` — soft mode. On an HTTP connection a warning is shown, but the file content is still read and displayed. Convenient for local development.
- `1` — strict mode. On an HTTP connection the file is not read, only the warning is displayed.

Regardless of the value, hosts `localhost` and `127.0.0.1` are always treated as secure.

### 11.2. `rv_html_mode`

Type: select.
Default value: `sanitize`.

- `sanitize` — `.html` documentation goes through the DOMDocument sanitizer with a whitelist. Rendered as HTML, but without dangerous tags and attributes.
- `plain` — `.html` documentation is displayed as source code in `<pre>`. Not rendered.

### 11.3. `rv_max_size`

Type: select.
Default value: `1048576` (1 MiB).

Allowed values:

- `1048576` — 1 MiB.
- `2097152` — 2 MiB.
- `3145728` — 3 MiB.
- `5242880` — 5 MiB.

If the file size exceeds the value, the file is not read and the `rv_error_too_large` message is displayed.

A value of `0` (if set manually in the database) disables the size check — use with caution.

<a id="functions-en"></a>
## 12. Function reference

All functions are declared in two files:

- `inc/readme_viewer.functions.php` — search and read.
- `inc/readme_viewer.markdown.php` — Markdown, HTML, and anchor processing.

### 12.1. `rv_is_valid_code(?string $code): bool`

**File:** `inc/readme_viewer.functions.php`.

Validates the extension code.

Conditions:

- Not `null`.
- Not an empty string.
- Length not more than 64 chars.
- Matches the regex `^[a-z][a-z0-9_\-]*$` (case-insensitive).

Returns `true` or `false`.

Accepts `?string`, so it's safe when `null` — returns `false`. This protects against fatal errors when called without parameters.

### 12.2. `rv_build_documentation_url(?string $code, bool $isModule): ?string`

**File:** `inc/readme_viewer.functions.php`.

Builds the README view page URL.

Algorithm:

1. Validates `code` via `rv_is_valid_code()`.
2. Searches the file via `rv_find_documentation()`.
3. If the file is not found — returns `null`.
4. If found — returns URL via `cot_url('readme_viewer', ['m' => 'view', 'type' => ..., 'code' => ...])`.

Used in the `admin.extensions.details` hook: if the function returns `null`, the "Documentation" button is not shown.

### 12.3. `rv_find_documentation(?string $code, bool $isModule): ?array`

**File:** `inc/readme_viewer.functions.php`.

Search orchestrator. Checks two priorities and returns the first found file.

Returns:

```php
[
    'path' => '/full/absolute/path/to/README.md',
    'ext'  => 'md',  // 'md' | 'html' | 'txt'
]
```

or `null` if the file is not found.

Logic:

1. Validates `code`.
2. Builds the extension folder path: `modules_dir` or `plugins_dir` + code.
3. Checks `is_dir($extDir)`. If the extension folder doesn't exist — immediately `null` (no point in searching).
4. Calls `rv_probe_dir($extDir . '/_dir_readme_viewer', ['README', 'readme'])`.
5. If it returned — returns the result.
6. Otherwise calls `rv_probe_dir(plugins_dir . '/readme_viewer/_dir_readme_viewer_list', [$code . '_README', $code . '_readme'])`.
7. If it returned — returns the result.
8. Otherwise `null`.

### 12.4. `rv_probe_dir(string $dir, array $baseNames): ?array`

**File:** `inc/readme_viewer.functions.php`.

Iterates over files in a directory by given names and extensions.

Parameters:

- `$dir` — directory path.
- `$baseNames` — array of names (e.g., `['README', 'readme']`).

Logic:

1. `realpath($dir)` — obtain the absolute directory path. If it fails or it's not a directory — `null`.
2. For each name from `$baseNames`:
   - For each extension from `['md', 'html', 'txt']`:
     - Builds the candidate: `$baseReal . DIRECTORY_SEPARATOR . $name . '.' . $ext`.
     - Checks `is_file()`.
     - `realpath($candidate)` — obtain the absolute file path.
     - Checks `strpos($real, $baseReal . DIRECTORY_SEPARATOR) === 0` — the real path must start with the base path plus separator. This cuts off:
       - path traversal via `..`;
       - symlink-escape outside the folder.
     - Checks `is_readable($real)`.
     - Checks `strtolower(pathinfo($real, PATHINFO_EXTENSION))` — the real extension must be in `['md', 'html', 'txt']`.
     - Returns `['path' => $real, 'ext' => $realExt]`.
3. If nothing found — `null`.

### 12.5. `rv_read_documentation(?string $code, bool $isModule): ?array`

**File:** `inc/readme_viewer.functions.php`.

Safe reading of the found file.

Algorithm:

1. Calls `rv_find_documentation()`.
2. If not found — `null`.
3. Reads `rv_max_size` from config (default 0 if not set).
4. If `$maxSize > 0 && filesize($path) > $maxSize` — returns an array with error `too_large`.
5. Reads content via `file_get_contents()`.
6. If `false` — returns an array with error `read_failed`.

Returned structure:

```php
[
    'content' => ?string,  // content or null on error
    'ext'     => string,   // 'md' | 'html' | 'txt'
    'path'    => string,   // absolute real path
    'error'   => ?string,  // null | 'too_large' | 'read_failed'
]
```

The `path` field is used in `readme_viewer.view.php` to build the relative path `RV_FILE_PATH` and file name `RV_FILE_NAME`.

### 12.6. `rv_markdown_to_html(string $md, string $baseUrl = ''): string`

**File:** `inc/readme_viewer.markdown.php`.

Markdown → HTML converter.

Logic:

1. Tries to include `lib/Parsedown.php`.
2. If the `Parsedown` class exists:
   - Creates an instance.
   - Disables safe mode: `setSafeMode(false)`, `setMarkupEscaped(false)`, `setBreaksEnabled(false)`.
   - Renders: `$rvHtml = $rvParser->text($md)`.
   - Applies `rv_add_heading_ids($rvHtml)`.
   - Passes the result through `rv_sanitize_html($rvHtml, $baseUrl)` — this sanitizer blocks dangerous tags and attributes, and absolutizes anchors.
   - Returns the result.
3. If `Parsedown` is unavailable — embedded fallback:
   - Normalizes newlines: `\r\n` and `\r` → `\n`.
   - Escapes everything via `htmlspecialchars()`.
   - Handles fenced code (` ```lang ... ``` `).
   - Handles inline code (` `code` `).
   - Handles headings (`#` through `######`).
   - Handles bold (`**text**`) and italic (`*text*`).
   - Handles links (`[text](url)`), http(s), relative, and anchors only.
   - Replaces newlines with `<br>`.
   - Applies `rv_add_heading_ids()`.
   - Applies `rv_absolutize_anchors()` if `$baseUrl` is not empty.

The `$baseUrl` parameter — absolute URL of the current page (e.g., `https://site.tld/index.php?e=readme_viewer&m=view&type=module&code=page`). Used for anchor absolutization.

### 12.7. `rv_sanitize_html(string $html, string $baseUrl = ''): string`

**File:** `inc/readme_viewer.markdown.php`.

HTML sanitizer via DOMDocument.

Algorithm:

1. Empty input — returns an empty string.
2. Wraps the input in `<div id="__rv_root__">` — required because `DOMDocument` handles fragments poorly.
3. Loads the HTML via `loadHTML()` with `LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD`.
4. Suppresses libxml warnings.
5. Finds the root container `__rv_root__`.
6. Recursively walks the nodes:
   - Elements outside the tag whitelist — removed with contents.
   - Attributes outside the attribute whitelist — removed.
   - Attributes `href`/`src` with invalid values — removed.
   - Non-DOMElement/non-DOMText nodes (comments, CDATA) — removed.
7. Serializes the wrapper's child nodes.
8. Applies `rv_absolutize_anchors()` if `$baseUrl` is not empty.
9. Returns the result.

If DOMDocument fails to parse the wrapper (`$root === null`), returns the fully escaped input via `htmlspecialchars()` — a safe fallback.

### 12.8. `rv_absolutize_anchors(string $html, string $baseUrl): string`

**File:** `inc/readme_viewer.markdown.php`.

Replaces relative anchors with absolute URLs.

Logic:

1. If `$baseUrl` is empty — returns the input as-is.
2. Strips trailing `#` from `$baseUrl` if present.
3. Regex `/\bhref\s*=\s*(["\'])(#[^"\']*)\1/i` finds all `href="#anchor"`.
4. Replaces with `href="<baseUrl>#anchor"`.

Used to work around the `<base href="...">` that Cotonti inserts into the theme header. Without absolutization, clicking a table of contents item would lead to a foreign page without plugin parameters.

### 12.9. `rv_slugify(string $text): string`

**File:** `inc/readme_viewer.markdown.php`.

Generates a slug for the heading `id`.

Logic:

1. `strip_tags()` — removes HTML tags from the text.
2. `mb_strtolower()` + `trim()` — lowercase and trim spaces.
3. `preg_replace('/[\s]+/u', '-', $text)` — spaces to hyphens.
4. `preg_replace('/[^\p{L}\p{N}\-]+/u', '', $text)` — remove everything except Unicode letters, digits, and hyphens.
5. `preg_replace('/-+/', '-', $text)` — collapse multiple hyphens into one.
6. `trim($text, '-')` — remove leading and trailing hyphens.

Example: `"Hello, world!"` → `"hello-world"` (without quotes).

Important: `\p{L}` includes Cyrillic, so "Контакты" → "контакты", not "kontakty". No transliteration is performed.

### 12.10. `rv_add_heading_ids(string $html): string`

**File:** `inc/readme_viewer.markdown.php`.

Adds `id` to `<h1>`–`<h6>` headings if not present.

Logic:

1. If `$html` has no headings — returns the input as-is.
2. Wraps the input in `<div id="__rv_ids__">`.
3. Loads via `DOMDocument::loadHTML()`.
4. Finds the root container.
5. Iterates over all elements:
   - Skips non-headings (`h1`–`h6`).
   - Skips headings with an already-set non-empty `id`.
   - Generates a slug via `rv_slugify($el->textContent)`.
   - If the slug is empty — skips (doesn't set `id`).
   - Checks uniqueness: if the slug is already used, appends `-2`, `-3`, and so on until unique.
   - Sets `id` on the heading.
6. Serializes and returns the result.

Example: two headings "Контакты" → the first gets `id="контакты"`, the second — `id="контакты-2"`.

<a id="markdown-en"></a>
## 13. Markdown converter

### 13.1. Primary mode (via Parsedown)

If `lib/Parsedown.php` is available, Markdown rendering goes through it.

Key Parsedown settings:

- **`setSafeMode(false)`** — safe mode is off. If safe mode were on, Parsedown would block any inline HTML in Markdown — this would turn `<a id="anchor"></a>` into text and break anchors.

- **`setMarkupEscaped(false)`** — safe inline HTML from the source is allowed. This lets `<a id="anchor"></a>`, `<span class="...">`, `<div>`, and other safe tags pass through.

- **`setBreaksEnabled(false)`** — a single newline (`\n`) inside a paragraph is NOT turned into `<br>`. This is the standard CommonMark behavior. For line breaks you need two newlines (empty line between paragraphs) or an explicit `<br>`.

- **Post-render DOMDocument sanitization** — after Parsedown produces HTML, the result goes through `rv_sanitize_html()`. This ensures no dangerous tag or attribute slips through, even with safe mode disabled. The sanitizer allows `<a id="...">`, `<span>`, `<div>`, tables, etc. — but blocks `<script>`, `<iframe>`, `<object>`, `<embed>`, `on*`, `javascript:`, and `data:`.

### 13.2. Fallback mode

If `Parsedown` is unavailable or cannot be loaded, an embedded minimal parser is used.

Supported constructs:

- **Fenced code**: ` ```lang\n...\n``` ` → `<pre class="rv-code"><code>...</code></pre>`.
- **Inline code**: `` `code` `` → `<code>code</code>`.
- **Headings**: `#` through `######` at the start of a line → `<h1>` through `<h6>`.
- **Bold**: `**text**` → `<strong>text</strong>`.
- **Italic**: `*text*` → `<em>text</em>` (only single asterisk, not inside `**`).
- **Links**: `[text](url)` → `<a href="url">text</a>`. Only `http://`, `https://`, relative (`/...`) and anchors (`#...`) are supported. External links get `target="_blank" rel="noopener noreferrer"`.
- **Line breaks**: single `\n` → `<br>` (unlike Parsedown).

Order of operations:

1. Newline normalization.
2. Escaping everything via `htmlspecialchars()`.
3. Fenced code (before inline elements so `#` inside code doesn't become a heading).
4. Inline code.
5. Headings (longest first: `######` before `#`, so `######` isn't consumed by `#`).
6. Bold, then italic (order matters, otherwise `**text**` may partially be swallowed by the italic rule).
7. Links.
8. `nl2br()`.

### 13.3. What's important to understand

- **Fallback is not a complete Markdown.** It covers the most common constructs but not tables, blockquotes, lists (`- item`, `1. item`), or horizontal rules (`---`). If your README uses these and Parsedown is for some reason unavailable, they will show as plain text.
- **Fallback escapes EVERYTHING before markup.** This means `<` and `>` in the source are always shown as text, not HTML. This is safe but less flexible.
- **Parsedown with safe mode OFF + DOMDocument sanitizer.** The sanitizer allows safe inline HTML from a whitelist but removes any dangerous tags and attributes. If you need complex HTML markup (tables with colspan, custom divs), use `.html` format instead of `.md`.

<a id="sanitizer-en"></a>
## 14. HTML sanitizer

The sanitizer is applied to `.html` documents in `rv_html_mode=sanitize` mode, and to Markdown-rendered HTML after Parsedown.

### 14.1. Mechanism

1. Wrap in `<div id="__rv_root__">`.
2. Load via `DOMDocument::loadHTML()`.
3. Recursively walk the tree.
4. Remove unsafe nodes and attributes.
5. Serialize back.

DOMDocument was chosen as a more reliable approach than regexes. Regexes handle nested tags, quoted attributes, comments, and CDATA poorly.

### 14.2. What is allowed

**Tags:**

```
p, br, hr,
h1, h2, h3, h4, h5, h6,
ul, ol, li,
pre, code,
blockquote,
strong, b, em, i, u, s, sub, sup,
a, img,
table, thead, tbody, tr, td, th, caption,
span, div
```

**Attributes:**

On any tag from the list:
- `class`
- `id`

On `<a>`:
- `href`
- `title`
- `rel`
- `target`

On `<img>`:
- `src`
- `alt`
- `title`
- `width`
- `height`

**Values of `href` and `src`:**

Validated by regex `#^(https?:|/|\#|mailto:)#i`. That is, allowed:

- `http://...`
- `https://...`
- `/...` (root-relative)
- `#...` (anchors)
- `mailto:...`

Everything else is removed, even if the attribute was in the whitelist.

### 14.3. What is removed

- **Tags outside the whitelist:** `<script>`, `<style>`, `<iframe>`, `<object>`, `<embed>`, `<form>`, `<input>`, `<button>`, `<video>`, `<audio>`, `<canvas>` and any others — removed with contents.
- **Attributes outside the whitelist:** `style`, `onclick`, `onerror`, `onload`, `onmouseover` and any other `on*` — removed.
- **Invalid `href`/`src`:** `javascript:alert(1)`, `data:text/html,...`, `vbscript:...`, `file://...` — removed.
- **Comments and CDATA:** removed.
- **Other nodes:** text nodes are preserved, everything else is removed.

### 14.4. Example

Input:

```html
<p>Hello, <b>world</b>!</p>
<script>alert('xss')</script>
<a href="javascript:alert(1)">click</a>
<a href="https://example.com" onclick="alert(1)">safe</a>
<img src="data:image/png;base64,..." alt="x">
```

Output:

```html
<p>Hello, <b>world</b>!</p>
<a>click</a>
<a href="https://example.com">safe</a>
<img alt="x">
```

The `<script>` tag is removed with contents. The first link's `href` is removed (invalid) — an empty `<a>click</a>` remains. The second link's `onclick` is removed, but `href` is kept. The `<img>`'s `src` is removed (data-URI not allowed), but `alt` is kept.

### 14.5. Important: sanitizer doesn't remove text

The sanitizer removes **elements** and **attributes**. Text content remains. For example:

```html
<p>Bad <span style="color: red">style</span></p>
```

Output:

```html
<p>Bad <span>style</span></p>
```

The `<span>` tag is in the whitelist, but `style` attribute is not, so the attribute is removed, but the text "style" remains.

<a id="anchors-en"></a>
## 15. Anchor and heading handling

### 15.1. The problem

Cotonti inserts a `<base href="https://site.tld/">` tag into the theme header. Per the HTML standard, the browser resolves all relative URLs against `base`, not the current page URL. This means that `<a href="#anchor">` in such a theme will lead not to `https://site.tld/current-page#anchor` but to `https://site.tld/#anchor`. With the current page's parameters, this is incompatible: clicking a table of contents item takes the user away from `?e=readme_viewer&m=view&...` to the site homepage with an anchor.

### 15.2. The solution

After Markdown/HTML rendering, `rv_absolutize_anchors($html, $baseUrl)` is applied. It finds all `href="#anchor"` and replaces them with `href="<baseUrl>#anchor"`, where `$baseUrl` is the absolute URL of the current page (`Cot::$sys['abs_url'] . cot_url('readme_viewer', [...])`).

This guarantees that clicking an anchor stays on the same page with the anchor.

### 15.3. Generating ids for headings

After Markdown/HTML rendering, `rv_add_heading_ids($html)` is applied. It:

1. Finds all `<h1>`–`<h6>` without `id`.
2. Generates `id` via `rv_slugify(textContent)`.
3. Handles collisions: if the slug is already taken, appends `-2`, `-3`, and so on.

### 15.4. The slug

Function `rv_slugify(string $text)`:

1. Removes HTML tags.
2. Lowercases.
3. Replaces spaces with hyphens.
4. Removes everything except Unicode letters, digits, and hyphens.
5. Collapses multiple hyphens into one.
6. Removes leading and trailing hyphens.

Examples:

| Source text | Slug |
|-------------|------|
| `Контакты` | `контакты` |
| `Что делает плагин` | `что-делает-плагин` |
| `URL-страницы` | `url-страницы` |
| `API: методы` | `api-методы` |
| `Hello, World!` | `hello-world` |

### 15.5. Impact on the table of contents

In a README file, the table of contents is usually written like this:

```markdown
1. [Introduction](#introduction)
2. [Features](#features)
```

Both anchors (`#introduction`, `#features`) are Latin. After rendering, `id="introduction"`, `id="features"` appear on the headings, and `href="#introduction"` is absolutized to `href="https://site.tld/current-page#introduction"`. Clicking works.

If you use Cyrillic anchors (`#введение`), the slug will be Cyrillic, and the anchor will match. However, explicit HTML anchors `<a id="introduction-en"></a>` placed in the Markdown source are more reliable and language-agnostic — because they don't depend on `rv_slugify` and Unicode normalization.

In this document, all explicit anchors use the `-en` suffix: `#introduction-en`, `#features-en`, and so on.

<a id="security-en"></a>
## 16. Security

### 16.1. URL parameter validation

Parameters `type` and `code` are extracted via `cot_import('...', 'G', 'ALP')`, which already cuts everything except `[A-Za-z0-9_-]`. Additionally, `code` is validated in `rv_is_valid_code()`:

- not `null`;
- not an empty string;
- length up to 64;
- regex `^[a-z][a-z0-9_\-]*$` (case-insensitive).

The `type` value is used as a boolean flag: strictly `=== 'module'` — module, otherwise plugin.

### 16.2. Protection against path traversal

The key protection is in `rv_probe_dir()`:

```php
$baseReal = realpath($dir);
// ...
$real = realpath($candidate);
if (strpos($real, $baseReal . DIRECTORY_SEPARATOR) !== 0) {
    continue;
}
```

`realpath()` expands `..`, symlinks, and normalizes the path. Then it checks that the real file path starts with the base path plus separator. This cuts off:

- Relative paths like `../../etc/passwd`.
- Symlinks pointing outside the allowed folder.

### 16.3. Protection against symlink-escape

`realpath()` expands symlinks. If inside `_dir_readme_viewer` there is `README.md` that is a symlink to `/etc/passwd`, `realpath()` returns `/etc/passwd`, the `strpos` check fails, and the file is not read.

### 16.4. Extension whitelist

Only `.md`, `.html`, `.txt` are allowed. Everything else is ignored — even if the file name is `README` but the extension is `.php`, `.sh`, `.json` or anything else.

### 16.5. Size limit

The `rv_max_size` setting limits the size of the file being read. On exceed — the file is not read. This protects against:

- Accidentally loading a gigabyte file with `.md` extension.
- A DoS attack via loading large files into an extension folder (if an attacker has FTP access).

### 16.6. HTML sanitization

`.html` documentation goes through the DOMDocument sanitizer with a whitelist (see [HTML sanitizer](#sanitizer-en)). This guarantees that even if the HTML file contains `<script>`, `onclick`, `javascript:` — they won't execute in the browser.

### 16.7. Markdown with Parsedown (safe mode off) + DOMDocument sanitizer

Markdown is rendered via Parsedown with `setSafeMode(false)` and `setMarkupEscaped(false)`, which allows safe inline HTML to pass through (needed for anchors). The rendered HTML is then passed through the DOMDocument sanitizer which removes any dangerous tags or attributes. This gives both functionality (anchors, inline HTML) and security (no XSS).

### 16.8. HTTPS policy

With `rv_enforce_https=1` and an HTTP connection, the file is not read. This protects against content leakage through an insecure channel (relevant if the README contains sensitive information).

### 16.9. Standard ACL

The plugin is protected by the standard Cotonti ACL. Per the setup file:

```
Auth_guests=0
Lock_guests=RW12345A
Auth_members=0
Lock_members=RW12345A
```

Only superadmins have access. Other groups are assigned via Admin → Rights. A direct visit to the view page URL without rights — 403.

### 16.10. Clickjacking and tab-nabbing protection

The "Documentation" button in the admin panel has `target="_blank"` and `rel="noopener noreferrer"`. The former opens in a new tab, the latter protects against tab-nabbing (where the new tab could control the original via `window.opener`).

<a id="template-tags-en"></a>
## 17. View page template tags

The `tpl/readme_viewer.view.tpl` template contains the `MAIN` block with nested blocks. All tags assigned by `inc/readme_viewer.view.php` are available in the template.

### 17.1. Block `RV_INSECURE`

Shown when the connection is not HTTPS and the host is not `localhost`/`127.0.0.1`.

| Tag | Description |
|-----|-------------|
| `{RV_INSECURE_WARNING}` | Warning text from the language file (`$L['rv_insecure_warning']`). |

### 17.2. Block `RV_OK`

Shown on successful file read.

| Tag | Description |
|-----|-------------|
| `{RV_TITLE}` | Extension code (e.g., `page`). |
| `{RV_CONTENT}` | Rendered documentation HTML (Markdown or HTML after conversion/sanitization, or text in `<pre>`). |
| `{RV_FILE_PATH}` | Relative path from the site root (e.g., `plugins/page/_dir_readme_viewer/README.md`). |
| `{RV_FILE_NAME}` | File name with extension (e.g., `README.md`). |
| `{RV_FILE_EXT}` | File extension without dot (`md`, `html`, `txt`). |

### 17.3. Block `RV_ERROR`

Shown on file read error (e.g., `too_large`, `read_failed`).

| Tag | Description |
|-----|-------------|
| `{RV_ERROR_TEXT}` | Localized error text. |

### 17.4. Block `RV_NOT_FOUND`

Shown if the documentation file is not found.

| Tag | Description |
|-----|-------------|
| `{PHP.L.rv_not_found}` | Text "documentation not found". |

### 17.5. Debug block

Appears only under specific conditions: cotcp plugin is active, `tplpathenableview` mode is on in cotcp settings, the user is a superadmin (`maingrp == 5`), and the function `cot_debug_tpl_url()` exists.

| Tag | Description |
|-----|-------------|
| `{PHP.L.cotcp_path_current_tpl}` | "Current template path" heading. |
| `{PHP|cot_debug_tpl_url()}` | Absolute path to the current template. |

### 17.6. CSS

The template contains an inline `<style>` with page CSS. Main classes:

- `.rv-doc` — container.
- `.rv-body` — content container.
- `.rv-pre` — `<pre>` blocks for `.txt` files.
- `.rv-code` — code block inside Markdown (created by fallback parser).
- `.rv-insecure-warning` — HTTPS warning.
- `.rv-not-found` — "not found" message.

Styles for inline code:

```css
.rv-body :not(pre) > code {
    color: #a9b8c8;
    background: #374351;
    font-weight: 400;
    font-size: 1rem;
    border-radius: .28571429rem;
    border: 1px solid #56606c;
    padding: 0.15em 0.4em;
    word-break: break-word;
}
```

Styles for `<pre>` block:

```css
.rv-body pre, .rv-pre {
    background: #171e26;
    color: #c4d2e1;
    border: 1px solid #2a3441;
    border-radius: 6px;
    padding: 16px;
    overflow: auto;
    max-height: 70vh;
    line-height: 1.45;
    font-size: 105%;
    white-space: pre;
    -webkit-overflow-scrolling: touch;
}
```

Styles for code inside `pre`:

```css
.rv-body pre > code, .rv-pre > code {
    background: transparent;
    color: inherit;
    font-size: inherit;
    line-height: inherit;
    padding: 0;
    border: 0;
    border-radius: 0;
    white-space: pre;
}
```

Styles for tables (mobile adaptation):

```css
.rv-body table {
    display: block;
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-collapse: collapse;
}
```

Styles for inline code in table cells (don't wrap, don't compress):

```css
.rv-body th code, .rv-body td code {
    white-space: nowrap;
    word-break: normal;
    overflow-wrap: normal;
    display: inline-block;
    min-width: max-content;
    max-width: none;
}
```

<a id="admin-en"></a>
## 18. Admin panel integration

### 18.1. How the hook works

The `admin.extensions.details` hook is called in `system/admin/admin.extensions.php` before parsing the DETAILS block. At that moment `$code`, `$is_module`, `$type`, `$t` are available.

The hook does `$t->assign([...])` — assigns four tags for the template.

### 18.2. What needs to be done in the template

File `system/admin/tpl/admin.extensions.tpl` (or, preferably, a theme override: `themes/admin/{admintheme}/admin.extensions.tpl`).

In the DETAILS block, inside `<div class="button-toolbar">`, after the Structure button, add:

```html
<!-- IF {ADMIN_EXTENSIONS_STRUCTURE_URL} -->
<a title="{PHP.L.Structure}" href="{ADMIN_EXTENSIONS_STRUCTURE_URL}" class="button large">{PHP.L.Structure}</a>
<!-- ENDIF -->

<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->
<a title="{ADMIN_EXTENSIONS_DOC_TITLE}"
   href="{ADMIN_EXTENSIONS_DOC_URL}"
   class="button large"
   target="_blank"
   rel="noopener noreferrer">{ADMIN_EXTENSIONS_DOC_LABEL}</a>
<!-- ENDIF -->
```

### 18.3. How the conditional block works

`<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->` — if `ADMIN_EXTENSIONS_HAS_DOC` = true, the block renders. If false — doesn't render.

In turn, `ADMIN_EXTENSIONS_HAS_DOC` is set in `readme_viewer.admin.extensions.details.php`:

```php
'ADMIN_EXTENSIONS_HAS_DOC' => ($rvUrl !== null),
```

So if `rv_build_documentation_url()` returned `null` (file not found), the tag equals `false`, and the button isn't shown.

### 18.4. Tag `ADMIN_EXTENSIONS_DOC_URL`

Contains the view page URL. If the file isn't found — an empty string `''`. The `<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->` block protects against rendering an empty link.

### 18.5. Tags `ADMIN_EXTENSIONS_DOC_TITLE` and `ADMIN_EXTENSIONS_DOC_LABEL`

Values from the language file:

- `ADMIN_EXTENSIONS_DOC_TITLE` = `$L['rv_open_doc']` = "Open extension documentation".
- `ADMIN_EXTENSIONS_DOC_LABEL` = `$L['rv_open_doc_short']` = "Documentation".

### 18.6. Theme override recommendation

Editing the core template will be lost on Cotonti update. Better:

1. Copy `system/admin/tpl/admin.extensions.tpl` to `themes/admin/{admintheme}/admin.extensions.tpl`.
2. Make the edit in the copied file.
3. The core will pick up the override via `cot_tplfile()`.

<a id="localization-en"></a>
## 19. Localization

### 19.1. Files

- `lang/readme_viewer.ru.lang.php` — Russian.
- `lang/readme_viewer.en.lang.php` — English (fallback for other languages).

### 19.2. View page strings

| Key | Value (en) |
|-----|------------|
| `rv_open_doc` | Open extension documentation |
| `rv_open_doc_short` | Documentation |
| `rv_not_found` | Documentation file not found. |
| `rv_insecure_warning` | Warning: this page is served over insecure HTTP. |
| `rv_error_too_large` | Documentation file is too large to display. |
| `rv_error_read_failed` | Unable to read documentation file. |

### 19.3. Admin metadata

| Key | Value (en) |
|-----|------------|
| `info_name` | README Viewer |
| `info_desc` | Safe viewer for extension README files from the admin panel. |
| `info_notes` | Looks for README.md / README.html / README.txt in the extension folder (_dir_readme_viewer), then in the plugin library (_dir_readme_viewer_list). |

### 19.4. Configuration strings

| Key | Value (en) |
|-----|------------|
| `cfg_rv_enforce_https` | Block viewing over HTTP |
| `cfg_rv_enforce_https_params` | `['0' => 'No (warn only)', '1' => 'Yes (block)']` |
| `cfg_rv_html_mode` | HTML documentation render mode |
| `cfg_rv_html_mode_params` | `['sanitize' => 'Sanitize (whitelist)', 'plain' => 'As source code']` |
| `cfg_rv_max_size` | Max file size, bytes |

### 19.5. Adding a new language

1. Copy `lang/readme_viewer.en.lang.php` to `lang/readme_viewer.<CODE>.lang.php`.
2. Translate all strings.
3. Ensure the language code is present in `Cot::$usr['lang']` (i.e., the corresponding Cotonti language pack is installed).

The core will pick up the file via `cot_langfile('readme_viewer', 'plug')` when `Cot::$usr['lang'] === '<CODE>'`.

<a id="examples-en"></a>
## 20. Usage examples

### 20.1. Example 1: README for a module in the local folder

Structure:

```
modules/page/_dir_readme_viewer/README.md
```

Content of `README.md`:

```markdown
# Page Module

The "Pages" module is the main content module of Cotonti.

## Categories

Configured in **Admin → Structure → Page**.

## Extra fields

**Admin → Extra fields → Page**.

## Templates

- `page.tpl` — page view;
- `page.list.tpl` — pages list;
- `page.add.tpl` — add form.
```

The `admin.php?m=extensions&a=details&mod=page` page will show the "Documentation" button. Clicking it opens a page with correctly rendered Markdown.

### 20.2. Example 2: README via the plugin library

If for some reason you can't or don't want to create a `_dir_readme_viewer` folder inside the extension, place the file in the library:

```
plugins/readme_viewer/_dir_readme_viewer_list/page_README.md
```

Note the `page_` prefix — it's required. A file `README.md` without a prefix in the library won't be found for the `page` module, only for an extension with an empty code (which is impossible).

### 20.3. Example 3: HTML documentation

File `plugins/contact/_dir_readme_viewer/README.html`:

```html
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Contact</title></head>
<body>
    <h1>Contact Plugin</h1>
    <p>Feedback form.</p>
    <h2>Installation</h2>
    <ol>
        <li>Upload to <code>plugins/contact</code>.</li>
        <li>Install via the admin panel.</li>
    </ol>
    <script>alert('this script will be removed by the sanitizer')</script>
</body>
</html>
```

With `rv_html_mode=sanitize` the `<script>` tag will be removed, everything else renders.

### 20.4. Example 4: Plain text

File `plugins/contact/_dir_readme_viewer/README.txt`:

```
CONTACT PLUGIN
==============

Feedback form.

INSTALLATION
------------
1. Upload the contact folder to plugins/.
2. Install via the admin panel.

SETTINGS
--------
- Administrator email
- Captcha (on/off)
```

Displayed in `<pre>` preserving formatting. All characters (including `<`, `>`, `&`) are shown as-is.

### 20.5. Example 5: Markdown with table of contents

```markdown
# Module Documentation

## Contents

1. [Features](#features-en)
2. [Installation](#installation-en)
3. [Configuration](#configuration-en)

## Features

- Feature 1
- Feature 2

## Installation

...

## Configuration

...
```

After rendering, all anchors will be absolutized and will work correctly even with `<base href>` in the theme.

For Cyrillic anchors to work, use explicit `<a id="myanchor"></a>` in the Markdown source, or make sure the anchor matches the slugified heading text (remembering that `rv_slugify` doesn't transliterate).

<a id="debugging-en"></a>
## 21. Debugging and diagnostics

### 21.1. Enabling Cotonti debugging

Enable `$cfg['debug_mode'] = true` in the config. This adds information about fired hooks.

### 21.2. Viewing paths

If the cotcp debug block is used (see above), you can see the absolute path to the current template. This helps understand which template was picked up.

### 21.3. Checking permissions

Verify that the user has `R` rights on `plug/readme_viewer`. Via admin panel: **Rights → readme_viewer**.

### 21.4. Checking files

Ensure that:

- The `_dir_readme_viewer` (or `_dir_readme_viewer_list`) folder exists.
- The file is named `README.md` (or `README.html`, `README.txt`, `readme.md`, `readme.html`, `readme.txt`).
- The case of names and extensions is strict.
- The file is readable by the web server (usually `644` is sufficient).

### 21.5. Checking PHP errors

PHP errors are written to the log (depends on configuration). Look for `readme_viewer` in `error_log` or `logs/`.

<a id="troubleshooting-en"></a>
## 22. Troubleshooting

### 22.1. "Documentation" button doesn't appear

Possible causes:

1. **README file not found.** Check the path and name. Ensure the case matches.
2. **Template edit not made.** Verify that `admin.extensions.tpl` (or the theme override) contains the `<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->` block.
3. **Plugin not installed or not active.** Check **Extensions → README Viewer**.
4. **Hook not registered.** Check `cot_plugins` in the DB (records with `pl_code = 'readme_viewer'`). If none — reinstall the plugin.

### 22.2. Button present, but page empty

Possible causes:

1. **XTemplate captured by the core.** Verify that in `inc/readme_viewer.view.php` the XTemplate is named `$rvTpl`, not `$t`. If named `$t` — `system/plugin.php` will capture it after `text('MAIN')` (which clears data), re-invoke `parse`/`out`, and the browser will show an empty block.
2. **PHP error.** Check `error_log`.
3. **Template issue.** Verify that `tpl/readme_viewer.view.tpl` contains the `MAIN` block and nested blocks.

### 22.3. Content renders but table of contents doesn't scroll

Possible causes:

1. **Parsedown escaped the HTML anchor.** Verify that `setSafeMode(false)` and `setMarkupEscaped(false)` are set. If safe mode is on, `<a id="introduction"></a>` becomes text `&lt;a id="introduction"&gt;&lt;/a&gt;`, and clicking `#introduction` finds nothing.
2. **Anchors not absolutized.** Check `$baseUrl` — it must be non-empty. In `inc/readme_viewer.view.php` `$rvViewUrl` is built when `$rvType !== null && $rvCode !== null`. If the URL is incorrect (e.g., missing parameters), `$rvViewUrl` will be empty, and absolutization won't work.
3. **Cyrillic anchors.** If your Markdown uses Latin anchors (`#introduction`), and headings are Cyrillic, the anchor won't find — because `rv_slugify` doesn't transliterate. Use Cyrillic anchors in the table of contents (`#введение`), or explicit `<a id="..."></a>` tags in the Markdown source, or `-en` suffixes for Latin anchors on Cyrillic-language content (as in this document).
4. **Heading id mismatch.** `<h2>1. Introduction</h2>` slugifies to `1-introduction` — if your TOC uses `#introduction`, it won't match. Use `<a id="introduction"></a>` explicitly, or adjust TOC to `#1-introduction`.

### 22.4. `.html` file shows as code

Check the `rv_html_mode` setting. If it's `plain` — this is expected behavior. To render, set `sanitize`.

### 22.5. `.md` file shows as plain text

Possible causes:

1. **Parsedown unavailable.** Check that `lib/Parsedown.php` exists and is included. If absent — the fallback is used, which also renders Markdown but with limitations.
2. **Wrong file extension.** If the file is named `README.MD` (uppercase) — won't be found. If `README.markdown` — extension not in the list, won't be found.

### 22.6. HTTP 500 on the view page

Possible causes:

1. **`$code` = null.** In older plugin versions `rv_is_valid_code()` accepted `string $code` — passing `null` caused a TypeError. In version 1.3.3 the signature is `?string`, so `null` is safely handled (returns `false`).
2. **Template error.** Check `tpl/readme_viewer.view.tpl` syntax.
3. **PHP error.** Check `error_log`.

### 22.7. HTTP warning under HTTPS

If you see the "served over insecure HTTP" warning but are sure you're on HTTPS, check:

1. `Cot::$sys['secure']` value (should be `true`).
2. `Cot::$sys['host']` value. If the host is an IP or a non-standard domain and is not `localhost`/`127.0.0.1`, the warning will appear.

<a id="faq-en"></a>
## 23. FAQ

### 23.1. Can the HTTPS warning be disabled?

Only by switching off strict mode (`rv_enforce_https=0`) — but then with HTTP the file is read, not blocked. The warning remains in soft mode.

### 23.2. Can Latin anchors be used in Markdown with Cyrillic headings?

With the current `rv_slugify` — only via explicit `<a id="myanchor"></a>` in the Markdown source, or by using `-en` suffix style as in this document. Cyrillic headings without explicit anchors get Cyrillic slugs.

### 23.3. Can documentation be shown for an uninstalled extension?

In the current version — no. The `admin.extensions.details` hook is only called when the extension is installed and its details page is opened. If the extension isn't installed (folder exists but no `cot_core` record), the details page returns 404.

### 23.4. Can the size limit be changed without reinstalling?

Yes, `rv_max_size` is stored in the database (`cot_config`). Change via admin panel: **Extensions → README Viewer → Settings**. Or directly in the DB: `UPDATE cot_config SET config_value = '2097152' WHERE config_owner = 'plug' AND config_cat = 'readme_viewer' AND config_name = 'rv_max_size'`.

### 23.5. Why Parsedown and not CommonMark?

Parsedown is a lightweight library (~50KB), fast, with a built-in safe mode. CommonMark (e.g., `league/commonmark`) is more feature-rich but requires Composer installation and weighs more. For extension documentation Parsedown is more than enough.

### 23.6. What if the README is larger than 5 MiB?

Increase `rv_max_size` directly in the DB (the `select` values in the settings don't cover more than 5 MiB). For example: `UPDATE cot_config SET config_value = '10485760' WHERE ...`.

### 23.7. Can HTML sanitization be disabled entirely?

No, this is a deliberate security limitation. If you need full HTML freedom — use `plain` mode (display as code). This is safe but not rendered.

### 23.8. Does the plugin work with a theme that doesn't use Bootstrap 5?

The plugin's CSS uses classes `.alert`, `.alert-info`, `.alert-secondary` (from Bootstrap). If the theme doesn't include Bootstrap, these blocks will be displayed without styles, but functionally. The main content (`.rv-body`) is styled with its own CSS and doesn't depend on Bootstrap.

### 23.9. Can the plugin be embedded in another plugin?

Technically — yes, via `cot_incfile('readme_viewer', 'plug')` and calling the functions. Note that some functions (`rv_markdown_to_html`) use `Cot::$cfg['plugins_dir']`, which may be unintuitive.

### 23.10. Is the plugin compatible with Cotonti v0.9.x?

Yes. The code uses standard APIs (`cot_auth`, `cot_url`, `cot_import`, `XTemplate`, `cot_langfile`, `cot_incfile`) available in both branches. The `Cot::` static facade is also available in both.

### 23.11. Why do explicit `<a id="..."></a>` tags matter?

`rv_add_heading_ids` slugifies the heading text. `<h2>1. Introduction</h2>` becomes `id="1-introduction"`. If your table of contents uses `#introduction`, clicking won't scroll — id mismatch. Explicit `<a id="introduction"></a>` before the heading provides a stable anchor regardless of slugify behavior. This is the recommended approach for professional documentation.

### 23.12. How do I know Parsedown is being used?

You can add a temporary `error_log(class_exists('Parsedown') ? 'Parsedown OK' : 'Parsedown missing')` at the start of `rv_markdown_to_html`. Or inspect the rendered HTML: if `rv-code` class is present on code blocks — the fallback parser was used (Parsedown doesn't add that class); if code blocks render as `<pre><code>` without `rv-code` — Parsedown.

<a id="removal-en"></a>
## 24. Plugin removal

### 24.1. Complete removal

1. **Admin → Extensions → README Viewer → Remove**. The core will remove:
   - Records in `cot_plugins` (hook bindings).
   - Records in `cot_config` (settings).
   - Records in `cot_auth` (rights).
   - Records in `cot_core` (metadata).
2. Delete the `plugins/readme_viewer/` folder via FTP.
3. Delete the button fragment from `admin.extensions.tpl` (or from the theme override).
4. Delete `_dir_readme_viewer` folders inside extensions (if they're no longer needed).
5. **Admin → Cache → Clear**.

### 24.2. What is NOT removed automatically

- Plugin files (the `plugins/readme_viewer/` folder).
- Template edit `admin.extensions.tpl`.
- `_dir_readme_viewer` folders inside extensions.
- Files in `_dir_readme_viewer_list/` (removed together with the plugin folder).

### 24.3. Disabling without removal

To temporarily disable: **Extensions → README Viewer → Pause**. The view page stops opening (404 or 403 depending on how the core handles an inactive plugin).

<a id="license-en"></a>
## 25. License

The plugin is distributed freely with Copyright (c) 2026 webitproff preserved.

- Repository: https://github.com/webitproff/readme_viewer-cotonti
- Support: https://abuyfile.com/forums/cotonti/custom/plugs/
- Marketplace: https://abuyfile.com/ru/market/cotonti/plugs/readme_viewer


---  
> RU
---  


# README Viewer для Cotonti

Плагин для CMF Cotonti v1.0 (Verona) и v0.9.x (Siena), добавляющий в админ-панель кнопку «Документация» на страницу деталей любого расширения — модуля или плагина. Кнопка открывает README-файл расширения в браузере с проверкой прав, размеров, HTTPS-политики и санитайзингом содержимого.

Версия документа: 1.3.3. Дата: 2026-11-21.

---

## Содержание

1. [Введение](#introduction)
2. [Почему важно иметь документацию под рукой](#why-docs)
3. [Возможности плагина](#features)
4. [Требования](#requirements)
5. [Установка](#installation)
6. [Структура файлов плагина](#file-structure)
7. [Общая бизнес-логика](#business-logic)
8. [Маршрутизация и URL](#url-routing)
9. [Алгоритм поиска файла README](#search-algorithm)
10. [Поддерживаемые форматы документов](#formats)
11. [Настройки плагина](#configuration)
12. [Справочник функций](#functions)
13. [Markdown-конвертер](#markdown)
14. [HTML-санитайзер](#sanitizer)
15. [Обработка якорей и заголовков](#anchors)
16. [Безопасность](#security)
17. [Теги шаблона страницы просмотра](#template-tags)
18. [Интеграция с админ-панелью](#admin)
19. [Локализация](#localization)
20. [Примеры использования](#examples)
21. [Отладка и диагностика](#debugging)
22. [Диагностика проблем](#troubleshooting)
23. [FAQ](#faq)
24. [Удаление плагина](#removal)
25. [Лицензия](#license)

---

<a id="introduction"></a>
## 1. Введение

README Viewer — это плагин для CMF Cotonti, решающий узкую, но важную административную задачу: дать администратору сайта быстрый и безопасный доступ к документации любого установленного расширения прямо из админ-панели, не отвлекаясь на FTP, файловые менеджеры или поиск нужного файла по директориям сервера.

Плагин добавляет на страницу деталей расширения (там, где администратор видит версию, автора, зависимости и кнопки управления) дополнительную кнопку «Документация». При её нажатии в новой вкладке открывается страница, отображающая содержимое README-файла расширения — с корректной типографикой, поддержкой оглавления, работоспособными внутренними ссылками и полным соблюдением политик безопасности сайта.

Технически плагин представляет собой классическое расширение Cotonti, состоящее из:

- Standalone-точки входа (`readme_viewer.php`), которая обрабатывает запрос `index.php?e=readme_viewer`.
- Хука `admin.extensions.details`, который добавляет кнопку на страницу деталей расширения.
- Набора функций для поиска и безопасного чтения README-файлов (`inc/readme_viewer.functions.php`).
- Исполнителя режима просмотра (`inc/readme_viewer.view.php`), который формирует HTML страницы.
- Конвертера Markdown и санитайзера HTML (`inc/readme_viewer.markdown.php`).
- Встроенной библиотеки Parsedown (`lib/Parsedown.php`) для Markdown-рендеринга.
- Шаблона XTemplate (`tpl/readme_viewer.view.tpl`).
- Языковых файлов (`lang/readme_viewer.ru.lang.php`, `lang/readme_viewer.en.lang.php`).

Плагин не требует изменения ядровых файлов Cotonti. Единственная правка, которая нужна для появления кнопки — однократное добавление фрагмента в шаблон `admin.extensions.tpl` (предпочтительно через переопределение в теме). Все операции поиска и чтения файлов изолированы от прямого доступа к файловой системе через строгий контроль путей.

<a id="why-docs"></a>
## 2. Почему важно иметь документацию под рукой

### 2.1. Скорость работы администратора

Когда администратор устанавливает, обновляет или отлаживает расширение, ему постоянно нужна информация: как называется нужный параметр конфигурации, что делает конкретная настройка, как правильно оформлять записи, какие права выставлять, как настроить структуру категорий. Если вся эта информация находится в README-файле расширения — но доступ к нему требует переключения на FTP-клиент, поиска папки, скачивания файла и открытия его локально — каждая такая операция отнимает от тридцати секунд до нескольких минут.

Плагин сокращает это до одного клика.

### 2.2. Снижение риска ошибок

Без быстрого доступа к документации администратор часто действует по интуиции или по памяти. Это увеличивает риск неправильной настройки: включить не ту опцию, установить несовместимые права, забыть про зависимость от другого расширения. Встроенная кнопка «Документация» делает правильный путь (прочитать README) более коротким, чем путь «попробовать наугад и посмотреть, что получится».

### 2.3. Единая точка хранения документации

Плагин поддерживает два независимых канала хранения README-файлов:

- **Локальный** — папка `_dir_readme_viewer` внутри каждого расширения. Здесь разработчик расширения (или администратор сайта вручную) кладёт актуальный README.
- **Централизованный** — папка `_dir_readme_viewer_list` внутри самого плагина. Здесь можно собрать документацию по всем установленным расширениям в одном месте, соблюдая простой контракт именования файлов.

Это позволяет администратору выбрать модель управления: обновлять README вместе с обновлением расширения или держать всю документацию в одном каталоге для быстрого доступа.

### 2.4. Безопасность без компромиссов

Наивная реализация «показать файл из папки расширения» создаёт угрозу: если администратор ошибётся в URL или намеренно попробует обратиться к файлу за пределами разрешённого каталога, приложение может раскрыть содержимое системных файлов. Плагин защищён от этого на каждом уровне:

- Валидация параметров URL (только `type` из белого списка `module`/`plug`, только `code` по регулярке).
- `realpath()`-разрешение путей с обязательной проверкой, что реальный путь файла находится строго внутри базовой директории.
- Белый список расширений (`.md`, `.html`, `.txt`).
- Санитайзинг HTML через `DOMDocument` с белым списком тегов и атрибутов.
- Markdown через Parsedown в safe mode.
- Ограничение размера файла из конфига.
- HTTPS-политика с жёстким или мягким режимом.
- Стандартная проверка прав через ACL Cotonti.

### 2.5. Единообразие внешнего вида документации

Плагин приводит все README к единому визуальному стилю: одинаковые заголовки, одинаковые блоки кода, одинаковая типографика для инлайн-кода, таблиц, списков. Это уменьшает визуальный шум и делает документацию предсказуемо читаемой вне зависимости от того, насколько аккуратно оформлен исходный Markdown-файл.

<a id="features"></a>
## 3. Возможности плагина

### 3.1. Кнопка «Документация» в админ-панели

На странице деталей любого установленного расширения:

- `admin.php?m=extensions&a=details&mod=page` — для модуля
- `admin.php?m=extensions&a=details&pl=contact` — для плагина

появляется кнопка «Документация». Она открывается в новой вкладке (`target="_blank"`) и защищена от tab-nabbing (`rel="noopener noreferrer"`). Кнопка показывается только если README-файл действительно найден.

### 3.2. Два приоритета поиска

Плагин ищет файл в двух местах:

1. Сначала — внутри папки самого расширения, в подкаталоге `_dir_readme_viewer`.
2. Если не нашел — в централизованной библиотеке плагина `_dir_readme_viewer_list`.

Приоритеты жестко зафиксированы: если найден файл на первом уровне, второй уровень не проверяется. Это даёт администратору контроль: локальный README всегда «перебивает» централизованный.

### 3.3. Три формата документов

Поддерживаются:

- `.md` — Markdown, рендерится в HTML через встроенный Parsedown в safe mode (или через встроенный fallback-парсер, если Parsedown недоступен).
- `.html` — HTML, проходит через DOMDocument-санитайзер с белым списком либо отображается как исходный код (настраивается).
- `.txt` — plain text, отображается в `<pre>` с полным экранированием.

Формат определяется по реальному расширению файла на диске, не по URL.

### 3.4. Работающие якоря в документации

Плагин автоматически:

- Генерирует `id` для каждого заголовка `<h1>`–`<h6>`, если он ещё не задан.
- Разрешает коллизии id: при совпадении к id добавляется `-2`, `-3` и так далее.
- Абсолютизирует относительные якоря `href="#anchor"` — превращает их в абсолютные URL вида `https://site.tld/index.php?e=readme_viewer&m=view&type=module&code=page#anchor`.

Последнее критически важно: Cotonti в шапке темы вставляет `<base href="...">`, из-за чего браузер резолвит относительные якоря не от текущего URL страницы, а от базы. Без абсолютизации клик по пункту оглавления уводил бы пользователя на чужую страницу без параметров плагина.

### 3.5. HTTPS-политика

Плагин различает защищённое (HTTPS) и незащищённое (HTTP) соединение. В настройках есть два режима:

- Мягкий: при HTTP показывается предупреждение, но содержимое файла всё равно отображается. Удобно для локальной разработки.
- Жёсткий: при HTTP файл не читается вовсе, отображается только предупреждение.

Хосты `localhost` и `127.0.0.1` всегда считаются защищёнными независимо от режима.

### 3.6. Ограничение размера файла

Настройка `rv_max_size` задаёт предел размера README в байтах. При превышении файл не читается, отображается сообщение «слишком большой». Защищает от случайной загрузки гигабайтного файла с расширением `.md` и падения PHP по памяти.

Доступные значения в UI: 1 MiB, 2 MiB, 3 MiB, 5 MiB.

### 3.7. Стандартный ACL

Плагин защищён стандартной системой прав Cotonti. По умолчанию доступ есть только у суперадминов, остальным группам права назначаются через Админку → Права.

### 3.8. Локализация

Плагин поставляется с русской и английской локализацией. Все строки вынесены в языковые файлы. Локализация следует стандартному механизму Cotonti: `cot_langfile()` автоматически подбирает файл по `Cot::$usr['lang']` с fallback на `en`.

<a id="requirements"></a>
## 4. Требования

- **Cotonti** v1.0 (Verona) или v0.9.x (Siena).
- **PHP** 7.3 или выше (совместим с PHP 8.x).
- Расширения PHP: `mbstring`, `dom` (входит в стандартную сборку PHP начиная с 5.x), `json`.
- Права суперадмина для установки.
- FTP-доступ или файловый менеджер для размещения README-файлов в папках расширений.

Плагин не требует дополнительных Composer-зависимостей: Parsedown встроен в дистрибутив.

<a id="installation"></a>
## 5. Установка

### 5.1. Загрузка файлов

Скопировать папку `readme_viewer` целиком в директорию `plugins/` установки Cotonti.

### 5.2. Установка через админ-панель

1. Войти в админ-панель как суперадмин.
2. Открыть **Расширения**.
3. В блоке «Плагины» найти «README Viewer».
4. Нажать **«Установить»**.
5. Убедиться, что все части (parts) плагина активны.

### 5.3. Настройка

**Расширения → README Viewer → Настройки**. Значения по умолчанию подходят для продакшена, но могут быть скорректированы.

### 5.4. Добавление кнопки в шаблон

Плагин сам по себе назначает все необходимые теги, но шаблон `admin.extensions.tpl` в ядре не содержит блока для кнопки. Нужна однократная правка шаблона.

Подробнее — раздел [Интеграция с админ-панелью](#admin).

### 5.5. Размещение README-файлов

Для каждого расширения, у которого нужна кнопка «Документация», создать вручную одну из двух структур:

Вариант 1 (приоритетный):

```
modules/<КОД_МОДУЛЯ>/_dir_readme_viewer/README.md
plugins/<КОД_ПЛАГИНА>/_dir_readme_viewer/README.md
```

Вариант 2 (централизованный):

```
plugins/readme_viewer/_dir_readme_viewer_list/<КОД>_README.md
```

Подробнее — разделы [Алгоритм поиска](#search-algorithm) и [Примеры](#examples).

<a id="file-structure"></a>
## 6. Структура файлов плагина

```
plugins/readme_viewer/
├── readme_viewer.setup.php
├── readme_viewer.php
├── readme_viewer.admin.extensions.details.php
├── inc/
│   ├── readme_viewer.functions.php
│   ├── readme_viewer.view.php
│   └── readme_viewer.markdown.php
├── lib/
│   └── Parsedown.php
├── tpl/
│   └── readme_viewer.view.tpl
├── lang/
│   ├── readme_viewer.ru.lang.php
│   └── readme_viewer.en.lang.php
└── _dir_readme_viewer_list/
    ├── .htaccess
    └── index.html
```

### 6.1. `readme_viewer.setup.php`

Метаданные плагина, читаемые ядром через `cot_infoget()` при установке (`system/extensions.php` → `cot_extension_install()`).

Содержит:

- Блок `[BEGIN_COT_EXT]` с метаданными: `Code`, `Name`, `Description`, `Version`, `Date`, `Author`, `Copyright`, `Notes`, `Auth_guests`, `Lock_guests`, `Auth_members`, `Lock_members`, `Requires_modules`, `Requires_plugins`, `Recommends_modules`, `Recommends_plugins`.
- Блок `[BEGIN_COT_EXT_CONFIG]` с настройками: `rv_enforce_https`, `rv_html_mode`, `rv_max_size`.

Значения auth/lock:

```
Auth_guests=0
Lock_guests=RW12345A
Auth_members=0
Lock_members=RW12345A
```

Это означает: на момент установки у всех групп, кроме суперадминов, прав нет. `cot_extension_install()` сам выдаст полные права (255 бит) только группе `COT_GROUP_SUPERADMINS`. Остальные группы получают права через Админку → Права.

### 6.2. `readme_viewer.php`

Standalone-точка входа. Активируется через хук `Hooks=standalone`. Ядро подключает этот файл при запросе `index.php?e=readme_viewer` (см. `system/plugin.php`).

Файл выполняет:

1. Устанавливает `Cot::$env['location'] = 'readme_viewer'`.
2. Подключает `inc/readme_viewer.functions.php` через `cot_incfile`.
3. Подключает языковой файл через `cot_langfile`.
4. Проверяет ACL через `cot_auth('plug', 'readme_viewer')` и `cot_block($usr['auth_read'])`.
5. Разбирает параметр `m` (по умолчанию `view`, любое другое значение сводится к `view`).
6. Подключает исполнителя режима — `inc/readme_viewer.view.php`.

### 6.3. `readme_viewer.admin.extensions.details.php`

Хук `admin.extensions.details`. Срабатывает в `system/admin/admin.extensions.php` внутри `case 'details'`, непосредственно перед `$t->parse('MAIN.DETAILS')`.

В области видимости хука:

- `$code` — код расширения.
- `$is_module` — тип (true для модуля).
- `$type` — 'module' или 'plug'.
- `$t` — XTemplate страницы админки.

Хук:

1. Проверяет наличие `$code` и `$is_module`.
2. Подключает `inc/readme_viewer.functions.php` и языковой файл.
3. Вызывает `rv_build_documentation_url($code, (bool) $is_module)`. Если README не найден — функция возвращает `null`.
4. Назначает теги для шаблона:

```
ADMIN_EXTENSIONS_HAS_DOC   — bool, есть ли README
ADMIN_EXTENSIONS_DOC_URL   — строка URL страницы просмотра или ''
ADMIN_EXTENSIONS_DOC_TITLE — подсказка (title) кнопки
ADMIN_EXTENSIONS_DOC_LABEL — текст кнопки
```

Значения title и label берутся из языкового файла (`Cot::$L['rv_open_doc']`, `Cot::$L['rv_open_doc_short']`).

### 6.4. `inc/readme_viewer.functions.php`

Публичные функции плагина для поиска и чтения README-файлов. Файл подключается автоматически через `system/plugin.php` (там есть `cot_incfile($extensionCode, 'plug', 'functions')`), а также явно из `readme_viewer.php` и `readme_viewer.admin.extensions.details.php`.

Подробное описание функций — раздел [Справочник функций](#functions).

### 6.5. `inc/readme_viewer.view.php`

Исполнитель режима `view`. Подключается из `readme_viewer.php`.

Задачи:

1. Валидация параметров `type` и `code` из URL.
2. Применение HTTPS-политики.
3. Чтение файла через `rv_read_documentation()`.
4. Рендеринг в XTemplate.
5. Формирование переменной `$pluginBody`, которую `system/plugin.php` вставит в стандартный контейнер.

**Важно:** XTemplate назван `$rvTpl`, а не `$t`. Это принципиально, потому что `system/plugin.php` в конце делает:

```php
$pluginTemplate = $t ?? null;
require_once Cot::$cfg['system_dir'] . '/header.php';
$t = $pluginTemplate;
if (isset($t) && is_object($t)) { $t->parse('MAIN'); $t->out('MAIN'); }
else { echo $pluginBody; }
```

Если бы XTemplate назывался `$t`, ядро перехватило бы его после `text('MAIN')` (который вычищает накопленные данные блока), повторно вызвало `parse`/`out` — и в браузере остался бы пустой блок. Поэтому использование `$rvTpl` — не косметика, а критическая деталь.

### 6.6. `inc/readme_viewer.markdown.php`

Конвертер Markdown → HTML и санитайзер HTML. Подключается из `inc/readme_viewer.view.php` через `cot_incfile('readme_viewer', 'plug', 'markdown')`.

Содержит:

- `rv_markdown_to_html()` — рендеринг Markdown.
- `rv_sanitize_html()` — санитайзинг HTML.
- `rv_absolutize_anchors()` — абсолютизация якорей.
- `rv_slugify()` — генерация слага из текста.
- `rv_add_heading_ids()` — добавление id к заголовкам.

Подробное описание — разделы [Markdown-конвертер](#markdown), [HTML-санитайзер](#sanitizer), [Обработка якорей](#anchors).

### 6.7. `lib/Parsedown.php`

Встроенная библиотека Parsedown версии 1.8.0. Используется в `rv_markdown_to_html()`. Подключается напрямую из папки плагина:

```php
require_once Cot::$cfg['plugins_dir'] . '/readme_viewer/lib/Parsedown.php';
```

Если файл отсутствует или не загружается по какой-то причине, используется встроенный fallback-парсер внутри `rv_markdown_to_html()`.

### 6.8. `tpl/readme_viewer.view.tpl`

XTemplate страницы просмотра. Содержит:

- `<style>` с CSS страницы.
- Блок `MAIN` с вложенными блоками `RV_INSECURE`, `RV_OK`, `RV_ERROR`, `RV_NOT_FOUND`.
- Отладочный блок для `cotcp` (появляется только если активен плагин cotcp и включён режим `tplpathenableview`).

Теги шаблона — раздел [Теги шаблона](#template-tags).

### 6.9. `lang/readme_viewer.ru.lang.php` и `lang/readme_viewer.en.lang.php`

Языковые файлы. Содержат строки страницы просмотра и метаданные для админки.

### 6.10. `_dir_readme_viewer_list/`

Централизованная библиотека README-файлов. Файл `.htaccess` запрещает листинг директории и прямой доступ к исполняемым файлам. Файл `index.html` — заглушка от directory listing на случай, если `.htaccess` проигнорируется веб-сервером.

<a id="business-logic"></a>
## 7. Общая бизнес-логика

Плагин реализует две независимые сценарии:

### 7.1. Сценарий «Кнопка в админке»

1. Администратор открывает страницу деталей расширения: `admin.php?m=extensions&a=details&mod=page`.
2. Ядро Cotonti в `system/admin/admin.extensions.php` формирует страницу и перед парсингом блока DETAILS подключает все хуки `admin.extensions.details`.
3. Наш хук (`readme_viewer.admin.extensions.details.php`) вызывается в этом контексте.
4. Хук вызывает `rv_build_documentation_url($code, (bool) $is_module)`.
5. Эта функция проверяет код расширения на валидность, ищет README через `rv_find_documentation()`. Если файл найден — строит URL страницы просмотра.
6. Хук назначает теги для шаблона (`ADMIN_EXTENSIONS_HAS_DOC`, `ADMIN_EXTENSIONS_DOC_URL`, `ADMIN_EXTENSIONS_DOC_TITLE`, `ADMIN_EXTENSIONS_DOC_LABEL`).
7. В шаблоне (после однократной правки) блок `<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->` рендерит кнопку.

### 7.2. Сценарий «Просмотр документации»

1. Пользователь кликает по кнопке «Документация» → браузер открывает URL вида `index.php?e=readme_viewer&m=view&type=module&code=page`.
2. Ядро Cotonti в `index.php` → `system/plugin.php` подключает `readme_viewer.php`.
3. `readme_viewer.php`:
   - Устанавливает location.
   - Подключает функции и языковые строки.
   - Проверяет ACL через `cot_auth('plug', 'readme_viewer')` и `cot_block()`.
   - Разбирает `m`, всегда получает `view`.
   - Подключает `inc/readme_viewer.view.php`.
4. `inc/readme_viewer.view.php`:
   - Разбирает `type` и `code` через `cot_import('...', 'G', 'ALP')`.
   - Строит `$rvViewUrl` — абсолютный URL текущей страницы для абсолютизации якорей.
   - Проверяет HTTPS-политику.
   - Проверяет `code` через `rv_is_valid_code()`.
   - Вызывает `rv_read_documentation()`.
   - В зависимости от расширения файла применяет разные стратегии рендеринга.
   - Назначает теги шаблона (`RV_TITLE`, `RV_CONTENT`, `RV_FILE_PATH`, `RV_FILE_NAME`, `RV_FILE_EXT`, `RV_ERROR_TEXT`, `RV_INSECURE_WARNING`).
   - Формирует `$pluginBody`.
5. `system/plugin.php` вставляет `$pluginBody` в стандартный контейнер темы.

<a id="url-routing"></a>
## 8. Маршрутизация и URL

### 8.1. Формат URL

```
index.php?e=readme_viewer&m=view&type=<module|plug>&code=<КОД_РАСШИРЕНИЯ>
```

### 8.2. Параметры

| Параметр | Допустимые значения | Описание |
|----------|---------------------|----------|
| `e` | `readme_viewer` | Код плагина. Жёстко задан ядром. |
| `m` | `view` | Режим. Любое другое значение принудительно сводится к `view`. |
| `type` | `module` \| `plug` | Тип расширения. Если `module` — поиск в `modules_dir`, иначе в `plugins_dir`. |
| `code` | `<код>` | Код расширения. Проверяется регуляркой `^[a-z][a-z0-9_\-]*$`, длина до 64. |

### 8.3. Примеры

Документация модуля `page`:
```
index.php?e=readme_viewer&m=view&type=module&code=page
```

Документация плагина `contact`:
```
index.php?e=readme_viewer&m=view&type=plug&code=contact
```

### 8.4. Что происходит при неизвестных значениях

- `type` не `module` и не `plug` → считается `plug` (по умолчанию).
- `code` содержит недопустимые символы или пустое → страница отображает «документация не найдена».
- `m` не `view` → принудительно `view`.

<a id="search-algorithm"></a>
## 9. Алгоритм поиска файла README

### 9.1. Приоритеты

Файл ищется в двух местах, в порядке приоритета:

**Приоритет 1** — папка самого расширения:

```
modules/<КОД>/_dir_readme_viewer/README.md
modules/<КОД>/_dir_readme_viewer/README.html
modules/<КОД>/_dir_readme_viewer/README.txt
modules/<КОД>/_dir_readme_viewer/readme.md
modules/<КОД>/_dir_readme_viewer/readme.html
modules/<КОД>/_dir_readme_viewer/readme.txt

plugins/<КОД>/_dir_readme_viewer/README.md
plugins/<КОД>/_dir_readme_viewer/README.html
plugins/<КОД>/_dir_readme_viewer/README.txt
plugins/<КОД>/_dir_readme_viewer/readme.md
plugins/<КОД>/_dir_readme_viewer/readme.html
plugins/<КОД>/_dir_readme_viewer/readme.txt
```

**Приоритет 2** — централизованная библиотека плагина:

```
plugins/readme_viewer/_dir_readme_viewer_list/<КОД>_README.md
plugins/readme_viewer/_dir_readme_viewer_list/<КОД>_README.html
plugins/readme_viewer/_dir_readme_viewer_list/<КОД>_README.txt
plugins/readme_viewer/_dir_readme_viewer_list/<КОД>_readme.md
plugins/readme_viewer/_dir_readme_viewer_list/<КОД>_readme.html
plugins/readme_viewer/_dir_readme_viewer_list/<КОД>_readme.txt
```

### 9.2. Порядок проверки внутри одного уровня

Проверка идёт в строго определённом порядке:

1. Сначала имя `README` (все заглавные), потом `readme` (все строчные).
2. Для каждого имени — сначала `.md`, потом `.html`, потом `.txt`.

То есть для приоритета 1 порядок такой:

1. `README.md`
2. `README.html`
3. `README.txt`
4. `readme.md`
5. `readme.html`
6. `readme.txt`

Первый найденный файл возвращается. Остальные не проверяются.

### 9.3. Что игнорируется

- `ReadMe.md`, `Readme.MD`, `README.MD` — игнорируются из-за неверного регистра.
- `README2.md`, `readme_old.md` — игнорируются, потому что имя не `README` и не `readme`.
- `README.php`, `README.sh`, `README.json` — игнорируются, потому что расширение вне белого списка.
- Симлинки, указывающие за пределы `_dir_readme_viewer` — игнорируются из-за проверки `realpath`.

### 9.4. Реализация

Алгоритм реализован в двух функциях:

- `rv_find_documentation(?string $code, bool $isModule): ?array` — оркестратор, проверяет папку расширения и библиотеку.
- `rv_probe_dir(string $dir, array $baseNames): ?array` — перебирает имена и расширения в конкретной директории, применяет защиту от path traversal и symlink-escape.

Подробнее — раздел [Справочник функций](#functions).

<a id="formats"></a>
## 10. Поддерживаемые форматы документов

### 10.1. Markdown (`.md`)

Рендерится через встроенный Parsedown в safe mode. Настройки:

- `setSafeMode(true)` — блокирует `<script>`, `<iframe>`, `<object>`, `<embed>`, атрибуты `on*`, `javascript:` и `data:` в ссылках.
- `setMarkupEscaped(true)` — не пропускает произвольный HTML из исходника.
- `setBreaksEnabled(false)` — одиночный перенос строки внутри абзаца не превращается в `<br>`.

После рендеринга применяются дополнительные преобразования:

1. `rv_add_heading_ids()` — добавление `id` к заголовкам.
2. `rv_absolutize_anchors()` — абсолютизация относительных якорей.

Если Parsedown недоступен, используется встроенный fallback-парсер (см. раздел [Markdown-конвертер](#markdown)).

### 10.2. HTML (`.html`)

Поведение определяется настройкой `rv_html_mode`:

- **`sanitize`** (по умолчанию) — файл проходит через DOMDocument-санитайзер с белым списком тегов и атрибутов. Всё, что не в списке, удаляется целиком.
- **`plain`** — файл отображается как исходный код в `<pre>` с полным экранированием. Безопасно, но не рендерится.

**Белый список тегов:**

```
p, br, hr, h1, h2, h3, h4, h5, h6,
ul, ol, li, pre, code, blockquote,
strong, b, em, i, u, s, sub, sup,
a, img, table, thead, tbody, tr, td, th, caption,
span, div
```

**Белый список атрибутов:**

- На любом теге: `class`, `id`.
- На `<a>`: `href`, `title`, `rel`, `target`.
- На `<img>`: `src`, `alt`, `title`, `width`, `height`.

**Валидация `href` и `src`:**

Разрешены только значения, начинающиеся с:

- `http://`
- `https://`
- `/` (относительные от корня)
- `#` (якоря)
- `mailto:`

Всё остальное (в том числе `javascript:`, `data:`, `ftp://`) — удаляется.

### 10.3. Text (`.txt`)

Отображается в `<pre>` с классом `rv-pre` и полным экранированием через `htmlspecialchars()`. Теги и любые символы `<`, `>`, `&` показываются как есть. Единственный формат, в котором не бывает ни санитайзинга, ни рендеринга — всегда безопасно по определению.

<a id="configuration"></a>
## 11. Настройки плагина

Доступны через **Расширения → README Viewer → Настройки**. Определены в `readme_viewer.setup.php` в блоке `[BEGIN_COT_EXT_CONFIG]`:

```
rv_enforce_https=00:radio:0:1:1:
rv_html_mode=01:select:sanitize,plain:
rv_max_size=02:select:1048576,2097152,3145728,5242880:
```

### 11.1. `rv_enforce_https`

Тип: radio (0 или 1).
Значение по умолчанию: `1`.

- `0` — мягкий режим. При HTTP-соединении показывается предупреждение, но содержимое файла всё равно читается и отображается. Удобно для локальной разработки.
- `1` — жёсткий режим. При HTTP-соединении файл не читается, отображается только предупреждение.

Независимо от значения, хосты `localhost` и `127.0.0.1` всегда считаются защищёнными.

### 11.2. `rv_html_mode`

Тип: select.
Значение по умолчанию: `sanitize`.

- `sanitize` — `.html`-документация проходит через DOMDocument-санитайзер с белым списком. Рендерится как HTML, но без опасных тегов и атрибутов.
- `plain` — `.html`-документация отображается как исходный код в `<pre>`. Не рендерится.

### 11.3. `rv_max_size`

Тип: select.
Значение по умолчанию: `1048576` (1 MiB).

Допустимые значения:

- `1048576` — 1 MiB.
- `2097152` — 2 MiB.
- `3145728` — 3 MiB.
- `5242880` — 5 MiB.

Если размер файла превышает значение, файл не читается, отображается сообщение `rv_error_too_large`.

Значение `0` (если задать вручную в БД) отключает проверку размера — использовать с осторожностью.

<a id="functions"></a>
## 12. Справочник функций

Все функции объявлены в двух файлах:

- `inc/readme_viewer.functions.php` — поиск и чтение.
- `inc/readme_viewer.markdown.php` — обработка Markdown, HTML, якорей.

### 12.1. `rv_is_valid_code(?string $code): bool`

**Файл:** `inc/readme_viewer.functions.php`.

Проверяет код расширения на валидность.

Условия:

- Не `null`.
- Не пустая строка.
- Длина не более 64 символов.
- Соответствует регулярке `^[a-z][a-z0-9_\-]*$` (без учёта регистра).

Возвращает `true` или `false`.

Принимает `?string`, поэтому безопасна при `null` — возвращает `false`. Это защищает от фатальных ошибок при обращении без параметров.

### 12.2. `rv_build_documentation_url(?string $code, bool $isModule): ?string`

**Файл:** `inc/readme_viewer.functions.php`.

Строит URL страницы просмотра README.

Алгоритм:

1. Проверяет `code` через `rv_is_valid_code()`.
2. Ищет файл через `rv_find_documentation()`.
3. Если файл не найден — возвращает `null`.
4. Если найден — возвращает URL через `cot_url('readme_viewer', ['m' => 'view', 'type' => ..., 'code' => ...])`.

Используется в хуке `admin.extensions.details`: если функция вернула `null`, кнопка «Документация» не отображается.

### 12.3. `rv_find_documentation(?string $code, bool $isModule): ?array`

**Файл:** `inc/readme_viewer.functions.php`.

Оркестратор поиска. Проверяет два приоритета и возвращает первый найденный файл.

Возвращает:

```php
[
    'path' => '/full/absolute/path/to/README.md',
    'ext'  => 'md',  // 'md' | 'html' | 'txt'
]
```

или `null`, если файл не найден.

Логика:

1. Проверяет `code`.
2. Строит путь к папке расширения: `modules_dir` или `plugins_dir` + код.
3. Проверяет `is_dir($extDir)`. Если папки расширения нет — сразу `null` (нет смысла искать).
4. Вызывает `rv_probe_dir($extDir . '/_dir_readme_viewer', ['README', 'readme'])`.
5. Если вернулось — возвращает результат.
6. Иначе вызывает `rv_probe_dir(plugins_dir . '/readme_viewer/_dir_readme_viewer_list', [$code . '_README', $code . '_readme'])`.
7. Если вернулось — возвращает результат.
8. Иначе `null`.

### 12.4. `rv_probe_dir(string $dir, array $baseNames): ?array`

**Файл:** `inc/readme_viewer.functions.php`.

Перебор файлов в директории по заданным именам и расширениям.

Параметры:

- `$dir` — путь к директории.
- `$baseNames` — массив имён (например, `['README', 'readme']`).

Логика:

1. `realpath($dir)` — получить абсолютный путь к директории. Если не удалось или это не директория — `null`.
2. Для каждого имени из `$baseNames`:
   - Для каждого расширения из `['md', 'html', 'txt']`:
     - Строит кандидата: `$baseReal . DIRECTORY_SEPARATOR . $name . '.' . $ext`.
     - Проверяет `is_file()`.
     - `realpath($candidate)` — получить абсолютный путь к файлу.
     - Проверяет `strpos($real, $baseReal . DIRECTORY_SEPARATOR) === 0` — реальный путь обязан начинаться с базового пути плюс разделитель. Это отсекает:
       - path traversal через `..`;
       - symlink-escape за пределы папки.
     - Проверяет `is_readable($real)`.
     - Проверяет `strtolower(pathinfo($real, PATHINFO_EXTENSION))` — реальное расширение обязано быть в `['md', 'html', 'txt']`.
     - Возвращает `['path' => $real, 'ext' => $realExt]`.
3. Если ничего не нашли — `null`.

### 12.5. `rv_read_documentation(?string $code, bool $isModule): ?array`

**Файл:** `inc/readme_viewer.functions.php`.

Безопасное чтение найденного файла.

Алгоритм:

1. Вызывает `rv_find_documentation()`.
2. Если не найдено — `null`.
3. Читает `rv_max_size` из конфига (по умолчанию 0, если не задан).
4. Если `$maxSize > 0 && filesize($path) > $maxSize` — возвращает массив с ошибкой `too_large`.
5. Читает содержимое через `file_get_contents()`.
6. Если `false` — возвращает массив с ошибкой `read_failed`.

Возвращаемая структура:

```php
[
    'content' => ?string,  // содержимое или null при ошибке
    'ext'     => string,   // 'md' | 'html' | 'txt'
    'path'    => string,   // абсолютный реальный путь
    'error'   => ?string,  // null | 'too_large' | 'read_failed'
]
```

Поле `path` используется во `readme_viewer.view.php` для формирования относительного пути `RV_FILE_PATH` и имени файла `RV_FILE_NAME`.

### 12.6. `rv_markdown_to_html(string $md, string $baseUrl = ''): string`

**Файл:** `inc/readme_viewer.markdown.php`.

Конвертер Markdown в HTML.

Логика:

1. Пытается подключить `lib/Parsedown.php`.
2. Если класс `Parsedown` существует:
   - Создаёт экземпляр.
   - Включает safe mode: `setSafeMode(true)`, `setMarkupEscaped(true)`, `setBreaksEnabled(false)`.
   - Рендерит: `$rvHtml = $rvParser->text($md)`.
   - Применяет `rv_add_heading_ids($rvHtml)`.
   - Применяет `rv_absolutize_anchors($rvHtml, $baseUrl)`, если `$baseUrl` не пустой.
   - Возвращает результат.
3. Если `Parsedown` недоступен — встроенный fallback:
   - Нормализует переносы: `\r\n` и `\r` → `\n`.
   - Экранирует всё через `htmlspecialchars()`.
   - Обрабатывает fenced code (` ```lang ... ``` `).
   - Обрабатывает inline code (` `code` `).
   - Обрабатывает заголовки (`#` до `######`).
   - Обрабатывает bold (`**text**`) и italic (`*text*`).
   - Обрабатывает ссылки (`[text](url)`), только http(s), относительные и якоря.
   - Заменяет переносы строк на `<br>`.
   - Применяет `rv_add_heading_ids()`.
   - Применяет `rv_absolutize_anchors()`, если `$baseUrl` не пустой.

Параметр `$baseUrl` — абсолютный URL текущей страницы (например, `https://site.tld/index.php?e=readme_viewer&m=view&type=module&code=page`). Используется для абсолютизации якорей.

### 12.7. `rv_sanitize_html(string $html, string $baseUrl = ''): string`

**Файл:** `inc/readme_viewer.markdown.php`.

Санитайзер HTML через DOMDocument.

Алгоритм:

1. Пустой вход — возвращает пустую строку.
2. Оборачивает вход в `<div id="__rv_root__">` — необходимо, потому что `DOMDocument` плохо работает с фрагментами.
3. Загружает HTML через `loadHTML()` с `LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD`.
4. Глушит libxml-предупреждения.
5. Находит корневой контейнер `__rv_root__`.
6. Рекурсивно обходит узлы:
   - Элементы вне белого списка тегов — удаляются вместе с содержимым.
   - Атрибуты вне белого списка — удаляются.
   - Атрибуты `href`/`src` с недопустимыми значениями — удаляются.
   - Не-DOMElement/не-DOMText узлы (комментарии, CDATA) — удаляются.
7. Сериализует дочерние узлы обёртки.
8. Применяет `rv_absolutize_anchors()`, если `$baseUrl` не пустой.
9. Возвращает результат.

Если DOMDocument не смог разобрать обёртку (`$root === null`), возвращает полностью экранированный вход через `htmlspecialchars()` — это безопасный fallback.

### 12.8. `rv_absolutize_anchors(string $html, string $baseUrl): string`

**Файл:** `inc/readme_viewer.markdown.php`.

Заменяет относительные якоря на абсолютные URL.

Логика:

1. Если `$baseUrl` пустой — возвращает вход как есть.
2. Убирает `#` в конце `$baseUrl`, если есть.
3. Регуляркой `/\bhref\s*=\s*(["\'])(#[^"\']*)\1/i` находит все `href="#anchor"`.
4. Заменяет на `href="<baseUrl>#anchor"`.

Используется для обхода проблемы с `<base href="...">`, который Cotonti вставляет в шапке темы. Без абсолютизации клик по пункту оглавления уводил бы на чужую страницу без параметров плагина.

### 12.9. `rv_slugify(string $text): string`

**Файл:** `inc/readme_viewer.markdown.php`.

Генерирует слаг для `id` заголовка.

Логика:

1. `strip_tags()` — убирает HTML-теги из текста.
2. `mb_strtolower()` + `trim()` — нижний регистр и обрезка пробелов.
3. `preg_replace('/[\s]+/u', '-', $text)` — пробелы в дефисы.
4. `preg_replace('/[^\p{L}\p{N}\-]+/u', '', $text)` — удалить всё, кроме Unicode-букв, цифр и дефиса.
5. `preg_replace('/-+/', '-', $text)` — сжать множественные дефисы в один.
6. `trim($text, '-')` — убрать дефисы по краям.

Пример: `"Hello, мир!"` → `"hello-мир"` (или `"hello-мир"` без кавычек).

Важно: `\p{L}` включает кириллицу, поэтому «Контакты» → `контакты`, а не `kontakty`. Транслитерации нет.

### 12.10. `rv_add_heading_ids(string $html): string`

**Файл:** `inc/readme_viewer.markdown.php`.

Добавляет `id` к заголовкам `<h1>`–`<h6>`, если его нет.

Логика:

1. Если в `$html` нет заголовков — возвращает вход как есть.
2. Оборачивает вход в `<div id="__rv_ids__">`.
3. Загружает через `DOMDocument::loadHTML()`.
4. Находит корневой контейнер.
5. Обходит все элементы:
   - Пропускает не-заголовки (`h1`–`h6`).
   - Пропускает заголовки с уже установленным непустым `id`.
   - Генерирует слаг через `rv_slugify($el->textContent)`.
   - Если слаг пустой — пропускает (не устанавливает `id`).
   - Проверяет уникальность: если слаг уже использован, добавляет суффикс `-2`, `-3` и так далее до уникальности.
   - Устанавливает `id` на заголовок.
6. Сериализует и возвращает результат.

Пример: два заголовка «Контакты» → первый получит `id="контакты"`, второй — `id="контакты-2"`.

<a id="markdown"></a>
## 13. Markdown-конвертер

### 13.1. Основной режим (через Parsedown)

Если `lib/Parsedown.php` доступен, рендеринг Markdown идёт через него.

Ключевые настройки Parsedown:

- **`setSafeMode(true)`** — safe mode. Блокирует потенциально опасные конструкции:
  - Теги: `<script>`, `<iframe>`, `<object>`, `<embed>` и подобные — не рендерятся.
  - Атрибуты: `on*` (обработчики событий) — вырезаются.
  - Ссылки: `javascript:` и `data:` — не пропускаются, заменяются на безопасный вид.
  - Встроенный HTML-тег `<style>` — не пропускается.

- **`setMarkupEscaped(true)`** — дополнительный уровень защиты. Весь HTML-код, встреченный в исходнике, экранируется как текст. Это значит, что `<div class="custom">` в исходнике `.md` отобразится как текст `&lt;div class="custom"&gt;`, а не как реальный `<div>`.

- **`setBreaksEnabled(false)`** — одиночный перенос строки (`\n`) внутри абзаца НЕ превращается в `<br>`. Это стандартное поведение для CommonMark-совместимых парсеров. Если нужны переносы, нужно использовать два перевода строки (пустая строка между абзацами) или явный `<br>` (который, впрочем, будет экранирован из-за `setMarkupEscaped(true)`).

### 13.2. Fallback-режим

Если `Parsedown` недоступен или не загрузился, используется встроенный минимальный парсер.

Поддерживаемые конструкции:

- **Fenced code**: ` ```lang\n...\n``` ` → `<pre class="rv-code"><code>...</code></pre>`.
- **Inline code**: `` `code` `` → `<code>code</code>`.
- **Заголовки**: `#` до `######` в начале строки → `<h1>` до `<h6>`.
- **Bold**: `**text**` → `<strong>text</strong>`.
- **Italic**: `*text*` → `<em>text</em>` (только одиночная звёздочка, не внутри `**`).
- **Ссылки**: `[text](url)` → `<a href="url">text</a>`. Поддерживаются только `http://`, `https://`, относительные (`/...`) и якоря (`#...`). Внешние получают `target="_blank" rel="noopener noreferrer"`.
- **Переносы**: одиночный `\n` → `<br>` (в отличие от Parsedown).

Порядок применения операций:

1. Нормализация переносов строк.
2. Экранирование всего через `htmlspecialchars()`.
3. Fenced code (до обработки inline-элементов, чтобы `#` внутри кода не стал заголовком).
4. Inline code.
5. Заголовки (от длинных к коротким: `######` до `#`, чтобы `######` не съелось правилом для `#`).
6. Bold, потом italic (порядок важен, иначе `**text**` может частично поглотиться italic-правилом).
7. Ссылки.
8. `nl2br()`.

### 13.3. Что важно понимать

- **Fallback — не полноценный Markdown.** Он покрывает наиболее частые конструкции, но не таблицы, не blockquote, не списки (`- item`, `1. item`), не горизонтальные линии (`---`). Если ваш README использует эти конструкции, а Parsedown по какой-то причине недоступен, они отобразятся как plain text.
- **Fallback экранирует ВСЁ до разметки.** Это значит, что `<` и `>` в исходнике всегда отображаются как текст, а не как HTML. Это безопасно, но менее гибко.
- **Parsedown с `setMarkupEscaped(true)` тоже экранирует весь HTML.** Если вы хотите использовать в README сложную HTML-вёрстку (таблицы с colspan, кастомные div и т.п.), вам придётся использовать формат `.html` вместо `.md` — для `.html` работает DOMDocument-санитайзер с белым списком.

<a id="sanitizer"></a>
## 14. HTML-санитайзер

Санитайзер применяется к `.html`-документам в режиме `rv_html_mode=sanitize`.

### 14.1. Механизм

1. Обёртка в `<div id="__rv_root__">`.
2. Загрузка через `DOMDocument::loadHTML()`.
3. Рекурсивный обход дерева.
4. Удаление небезопасных узлов и атрибутов.
5. Сериализация обратно.

DOMDocument был выбран как более надёжный способ, чем регулярки. Регулярки плохо справляются с вложенными тегами, атрибутами с кавычками внутри, комментариями и CDATA.

### 14.2. Что разрешено

**Теги:**

```
p, br, hr,
h1, h2, h3, h4, h5, h6,
ul, ol, li,
pre, code,
blockquote,
strong, b, em, i, u, s, sub, sup,
a, img,
table, thead, tbody, tr, td, th, caption,
span, div
```

**Атрибуты:**

На любом теге из списка:
- `class`
- `id`

На `<a>`:
- `href`
- `title`
- `rel`
- `target`

На `<img>`:
- `src`
- `alt`
- `title`
- `width`
- `height`

**Значения `href` и `src`:**

Валидируются регуляркой `#^(https?:|/|\#|mailto:)#i`. То есть разрешены:

- `http://...`
- `https://...`
- `/...` (относительные от корня)
- `#...` (якоря)
- `mailto:...`

Всё остальное удаляется, даже если атрибут был в белом списке.

### 14.3. Что удаляется

- **Теги вне белого списка:** `<script>`, `<style>`, `<iframe>`, `<object>`, `<embed>`, `<form>`, `<input>`, `<button>`, `<video>`, `<audio>`, `<canvas>` и любые другие — удаляются вместе с содержимым.
- **Атрибуты вне белого списка:** `style`, `onclick`, `onerror`, `onload`, `onmouseover` и любые другие `on*` — удаляются.
- **Невалидные `href`/`src`:** `javascript:alert(1)`, `data:text/html,...`, `vbscript:...`, `file://...` — удаляются.
- **Комментарии и CDATA:** удаляются.
- **Прочие узлы:** текстовые узлы сохраняются, всё остальное — удаляется.

### 14.4. Пример

Вход:

```html
<p>Привет, <b>мир</b>!</p>
<script>alert('xss')</script>
<a href="javascript:alert(1)">клик</a>
<a href="https://example.com" onclick="alert(1)">безопасно</a>
<img src="data:image/png;base64,..." alt="x">
```

Выход:

```html
<p>Привет, <b>мир</b>!</p>
<a>клик</a>
<a href="https://example.com">безопасно</a>
<img alt="x">
```

Тег `<script>` удалён вместе с содержимым. У первой ссылки удалён `href` (невалидный) — остался пустой `<a>клик</a>`. У второй ссылки удалён `onclick`, но `href` сохранён. У `<img>` удалён `src` (data-URI не разрешён), но `alt` сохранён.

### 14.5. Важно: санитайзер не удаляет текст

Санитайзер удаляет **элементы** и **атрибуты**. Текстовое содержимое остаётся. Например:

```html
<p>Плохой <span style="color: red">стиль</span></p>
```

Выход:

```html
<p>Плохой <span>стиль</span></p>
```

Тег `<span>` в белом списке, атрибут `style` — нет, поэтому атрибут удалён, но текст «стиль» остался.

<a id="anchors"></a>
## 15. Обработка якорей и заголовков

### 15.1. Проблема

Cotonti в шапке темы ставит тег `<base href="https://site.tld/">`. По стандарту HTML, браузер резолвит все относительные URL относительно `base`, а не текущего URL страницы. Это значит, что `<a href="#anchor">` в такой теме будет вести не на `https://site.tld/current-page#anchor`, а на `https://site.tld/#anchor`. С параметрами текущей страницы это несовместимо: клик по пункту оглавления уводит пользователя с `?e=readme_viewer&m=view&...` на главную сайта с якорем.

### 15.2. Решение

После рендеринга Markdown/HTML применяется `rv_absolutize_anchors($html, $baseUrl)`. Она находит все `href="#anchor"` и заменяет их на `href="<baseUrl>#anchor"`, где `$baseUrl` — абсолютный URL текущей страницы (`Cot::$sys['abs_url'] . cot_url('readme_viewer', [...])`).

Это гарантирует, что клик по якорю остаётся на той же странице с якорем.

### 15.3. Генерация id для заголовков

После рендеринга Markdown/HTML применяется `rv_add_heading_ids($html)`. Она:

1. Находит все `<h1>`–`<h6>` без `id`.
2. Генерирует `id` через `rv_slugify(textContent)`.
3. Обрабатывает коллизии: если слаг уже занят — добавляет суффикс `-2`, `-3` и так далее.

### 15.4. Слаг

Функция `rv_slugify(string $text)`:

1. Убирает HTML-теги.
2. Приводит к нижнему регистру.
3. Пробелы заменяет на дефисы.
4. Удаляет всё, кроме Unicode-букв, цифр и дефиса.
5. Сжимает множественные дефисы в один.
6. Убирает дефисы по краям.

Примеры:

| Исходный текст | Слаг |
|----------------|------|
| `Контакты` | `контакты` |
| `Что делает плагин` | `что-делает-плагин` |
| `URL-страницы` | `url-страницы` |
| `API: методы` | `api-методы` |
| `Hello, World!` | `hello-world` |

### 15.5. Влияние на оглавление

В README-файле оглавление обычно делается так:

```markdown
1. [Введение](#введение)
2. [Возможности](#возможности)
```

Оба якоря (`#введение`, `#возможности`) — кириллические. После рендеринга в заголовках появятся `id="введение"`, `id="возможности"`, а `href="#введение"` будет абсолютизирован до `href="https://site.tld/current-page#введение"`. Клик работает.

Если требуется использовать латинские якоря (например, `#introduction`), нужно либо:

- Использовать английские заголовки в Markdown (тогда слаг будет латинским).
- Или модифицировать `rv_slugify` для транслитерации кириллицы в латиницу.

В текущей реализации (версия 1.3.3) `rv_slugify` транслитерацию не делает, поэтому слаг кириллического заголовка — кириллический.

<a id="security"></a>
## 16. Безопасность

### 16.1. Валидация параметров URL

Параметры `type` и `code` извлекаются через `cot_import('...', 'G', 'ALP')`, что уже отсекает всё, кроме `[A-Za-z0-9_-]`. Дополнительно `code` валидируется в `rv_is_valid_code()`:

- не `null`;
- не пустая строка;
- длина до 64;
- регулярка `^[a-z][a-z0-9_\-]*$` (без учёта регистра).

Значение `type` используется как булев флаг: если строго `=== 'module'` — модуль, иначе плагин.

### 16.2. Защита от path traversal

Ключевая защита реализована в `rv_probe_dir()`:

```php
$baseReal = realpath($dir);
// ...
$real = realpath($candidate);
if (strpos($real, $baseReal . DIRECTORY_SEPARATOR) !== 0) {
    continue;
}
```

`realpath()` раскрывает `..`, симлинки и нормализует путь. Затем проверяется, что реальный путь файла начинается с базового пути + разделителя. Это отсекает:

- Относительные пути вида `../../etc/passwd`.
- Симлинки, указывающие за пределы разрешённой папки.

### 16.3. Защита от symlink-escape

`realpath()` раскрывает симлинки. Если внутри `_dir_readme_viewer` лежит `README.md`, который является симлинком на `/etc/passwd`, `realpath()` вернёт `/etc/passwd`, проверка `strpos` не пройдёт, файл не будет прочитан.

### 16.4. Белый список расширений

Разрешены только `.md`, `.html`, `.txt`. Всё остальное игнорируется — даже если имя файла `README`, но расширение `.php`, `.sh`, `.json` или любое другое.

### 16.5. Ограничение размера

Настройка `rv_max_size` ограничивает размер читаемого файла. При превышении — файл не читается. Это защищает от:

- Случайной загрузки гигабайтного файла с расширением `.md`.
- DoS-атаки через загрузку больших файлов в папку расширения (если у злоумышленника есть доступ к FTP).

### 16.6. Санитайзинг HTML

`.html`-документация проходит через DOMDocument-санитайзер с белым списком (см. [HTML-санитайзер](#sanitizer)). Это гарантирует, что даже если в HTML-файле есть `<script>`, `onclick`, `javascript:` — они не исполнятся в браузере.

### 16.7. Markdown через Parsedown safe mode

Parsedown с `setSafeMode(true)` и `setMarkupEscaped(true)` не пропускает опасные конструкции. Это защищает от XSS через Markdown-файл.

### 16.8. HTTPS-политика

При `rv_enforce_https=1` и HTTP-соединении файл не читается. Это защищает от утечки содержимого через незащищённый канал (актуально, если README содержит чувствительную информацию).

### 16.9. Стандартный ACL

Плагин защищён стандартным ACL Cotonti. По setup-файлу:

```
Auth_guests=0
Lock_guests=RW12345A
Auth_members=0
Lock_members=RW12345A
```

Доступ есть только у суперадминов. Остальным группам назначается через Админку → Права. Прямой заход на URL страницы просмотра без прав — 403.

### 16.10. Защита от кликджекинга и tab-nabbing

Кнопка «Документация» в админке имеет `target="_blank"` и `rel="noopener noreferrer"`. Первое открывает в новой вкладке, второе защищает от tab-nabbing (когда новая вкладка через `window.opener` может управлять оригинальной).

<a id="template-tags"></a>
## 17. Теги шаблона страницы просмотра

Шаблон `tpl/readme_viewer.view.tpl` содержит блок `MAIN` с вложенными блоками. Все теги, которые назначает `inc/readme_viewer.view.php`, доступны в шаблоне.

### 17.1. Блок `RV_INSECURE`

Показывается, когда соединение не HTTPS и хост не `localhost`/`127.0.0.1`.

| Тег | Описание |
|-----|----------|
| `{RV_INSECURE_WARNING}` | Текст предупреждения из языкового файла (`$L['rv_insecure_warning']`). |

### 17.2. Блок `RV_OK`

Показывается при успешном чтении файла.

| Тег | Описание |
|-----|----------|
| `{RV_TITLE}` | Код расширения (например, `page`). |
| `{RV_CONTENT}` | Отрендеренный HTML документации (Markdown или HTML после конвертации/санитайзинга, или текст в `<pre>`). |
| `{RV_FILE_PATH}` | Относительный путь к файлу от корня сайта (например, `plugins/page/_dir_readme_viewer/README.md`). |
| `{RV_FILE_NAME}` | Имя файла с расширением (например, `README.md`). |
| `{RV_FILE_EXT}` | Расширение файла без точки (`md`, `html`, `txt`). |

### 17.3. Блок `RV_ERROR`

Показывается при ошибке чтения файла (например, `too_large`, `read_failed`).

| Тег | Описание |
|-----|----------|
| `{RV_ERROR_TEXT}` | Локализованный текст ошибки. |

### 17.4. Блок `RV_NOT_FOUND`

Показывается, если файл документации не найден.

| Тег | Описание |
|-----|----------|
| `{PHP.L.rv_not_found}` | Текст «документация не найдена». |

### 17.5. Отладочный блок

Появляется только при определённых условиях: активен плагин `cotcp`, включён режим `tplpathenableview` в настройках cotcp, пользователь — суперадмин (`maingrp == 5`), существует функция `cot_debug_tpl_url()`.

| Тег | Описание |
|-----|----------|
| `{PHP.L.cotcp_path_current_tpl}` | Заголовок «Путь текущего шаблона». |
| `{PHP|cot_debug_tpl_url()}` | Абсолютный путь к текущему шаблону. |

### 17.6. CSS

Шаблон содержит inline `<style>` с CSS страницы. Основные классы:

- `.rv-doc` — контейнер.
- `.rv-body` — контейнер содержимого.
- `.rv-pre` — блоки `<pre>` для `.txt`-файлов.
- `.rv-code` — блок кода внутри Markdown (создаётся fallback-парсером).
- `.rv-insecure-warning` — предупреждение HTTPS.
- `.rv-not-found` — сообщение «не найдено».

Стили для инлайн-кода:

```css
.rv-body :not(pre) > code {
    color: #a9b8c8;
    background: #374351;
    font-weight: 400;
    font-size: 1rem;
    border-radius: .28571429rem;
    border: 1px solid #56606c;
    padding: 0.15em 0.4em;
    word-break: break-word;
}
```

Стили для блока `<pre>`:

```css
.rv-body pre, .rv-pre {
    background: #171e26;
    color: #c4d2e1;
    border: 1px solid #2a3441;
    border-radius: 6px;
    padding: 16px;
    overflow: auto;
    max-height: 70vh;
    line-height: 1.45;
    font-size: 105%;
    white-space: pre;
    -webkit-overflow-scrolling: touch;
}
```

Стили для кода внутри `pre`:

```css
.rv-body pre > code, .rv-pre > code {
    background: transparent;
    color: inherit;
    font-size: inherit;
    line-height: inherit;
    padding: 0;
    border: 0;
    border-radius: 0;
    white-space: pre;
}
```

Стили для таблиц (мобильная адаптация):

```css
.rv-body table {
    display: block;
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-collapse: collapse;
}
```

Стили для инлайн-кода в ячейках таблиц (не переносить, не сжимать):

```css
.rv-body th code, .rv-body td code {
    white-space: nowrap;
    word-break: normal;
    overflow-wrap: normal;
    display: inline-block;
    min-width: max-content;
    max-width: none;
}
```

<a id="admin"></a>
## 18. Интеграция с админ-панелью

### 18.1. Как работает хук

Хук `admin.extensions.details` вызывается в `system/admin/admin.extensions.php` перед парсингом блока DETAILS. В этот момент доступны `$code`, `$is_module`, `$type`, `$t`.

Хук делает `$t->assign([...])` — назначает четыре тега для шаблона.

### 18.2. Что нужно сделать в шаблоне

Файл `system/admin/tpl/admin.extensions.tpl` (или, предпочтительно, переопределение в теме: `themes/admin/{admintheme}/admin.extensions.tpl`).

В блоке DETAILS, внутри `<div class="button-toolbar">`, после кнопки Structure, добавить:

```html
<!-- IF {ADMIN_EXTENSIONS_STRUCTURE_URL} -->
<a title="{PHP.L.Structure}" href="{ADMIN_EXTENSIONS_STRUCTURE_URL}" class="button large">{PHP.L.Structure}</a>
<!-- ENDIF -->

<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->
<a title="{ADMIN_EXTENSIONS_DOC_TITLE}"
   href="{ADMIN_EXTENSIONS_DOC_URL}"
   class="button large"
   target="_blank"
   rel="noopener noreferrer">{ADMIN_EXTENSIONS_DOC_LABEL}</a>
<!-- ENDIF -->
```

### 18.3. Как работает условный блок

`<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->` — если `ADMIN_EXTENSIONS_HAS_DOC` = true, блок рендерится. Если false — не рендерится.

В свою очередь, `ADMIN_EXTENSIONS_HAS_DOC` выставляется в `readme_viewer.admin.extensions.details.php`:

```php
'ADMIN_EXTENSIONS_HAS_DOC' => ($rvUrl !== null),
```

То есть если `rv_build_documentation_url()` вернула `null` (файл не найден), тег равен `false`, кнопка не показывается.

### 18.4. Тег `ADMIN_EXTENSIONS_DOC_URL`

Содержит URL страницы просмотра. Если файл не найден — пустая строка `''`. Блок `<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->` защищает от рендера пустой ссылки.

### 18.5. Теги `ADMIN_EXTENSIONS_DOC_TITLE` и `ADMIN_EXTENSIONS_DOC_LABEL`

Значения из языкового файла:

- `ADMIN_EXTENSIONS_DOC_TITLE` = `$L['rv_open_doc']` = «Открыть документацию расширения».
- `ADMIN_EXTENSIONS_DOC_LABEL` = `$L['rv_open_doc_short']` = «Документация».

### 18.6. Рекомендация по переопределению в теме

Правка ядрового шаблона потеряется при обновлении Cotonti. Лучше:

1. Скопировать `system/admin/tpl/admin.extensions.tpl` в `themes/admin/{admintheme}/admin.extensions.tpl`.
2. Внести правку в скопированный файл.
3. Ядро само подхватит переопределение через `cot_tplfile()`.

<a id="localization"></a>
## 19. Локализация

### 19.1. Файлы

- `lang/readme_viewer.ru.lang.php` — русский.
- `lang/readme_viewer.en.lang.php` — английский (fallback для других языков).

### 19.2. Строки страницы просмотра

| Ключ | Значение (ru) |
|------|--------------|
| `rv_open_doc` | Открыть документацию расширения |
| `rv_open_doc_short` | Документация |
| `rv_not_found` | Файл документации не найден. |
| `rv_insecure_warning` | Внимание: страница открыта по незащищённому HTTP-соединению. |
| `rv_error_too_large` | Файл документации слишком большой для отображения. |
| `rv_error_read_failed` | Не удалось прочитать файл документации. |

### 19.3. Метаданные для админки

| Ключ | Значение (ru) |
|------|--------------|
| `info_name` | README Viewer |
| `info_desc` | Безопасный просмотр README-документации расширений из админ-панели. |
| `info_notes` | Ищет README.md / README.html / README.txt в папке расширения (_dir_readme_viewer), затем в библиотеке плагина (_dir_readme_viewer_list). |

### 19.4. Строки конфига

| Ключ | Значение (ru) |
|------|--------------|
| `cfg_rv_enforce_https` | Запрещать просмотр по HTTP |
| `cfg_rv_enforce_https_params` | `['0' => 'Нет (только предупреждение)', '1' => 'Да (блокировать)']` |
| `cfg_rv_html_mode` | Режим отображения HTML-документации |
| `cfg_rv_html_mode_params` | `['sanitize' => 'Санитайзинг (белый список)', 'plain' => 'Как исходный код']` |
| `cfg_rv_max_size` | Максимальный размер файла, байт |

### 19.5. Как добавить новый язык

1. Скопировать `lang/readme_viewer.en.lang.php` в `lang/readme_viewer.<КОД>.lang.php`.
2. Перевести все строки.
3. Убедиться, что код языка есть в `Cot::$usr['lang']` (то есть установлен соответствующий языковой пакет Cotonti).

Ядро подхватит файл через `cot_langfile('readme_viewer', 'plug')` при `Cot::$usr['lang'] === '<КОД>'`.

<a id="examples"></a>
## 20. Примеры использования

### 20.1. Пример 1: README для модуля через локальную папку

Структура:

```
modules/page/_dir_readme_viewer/README.md
```

Содержимое `README.md`:

```markdown
# Модуль Page

Модуль «Страницы» — основной контент-модуль Cotonti.

## Категории

Настраиваются в **Админка → Структура → Page**.

## Дополнительные поля

**Админка → Дополнительные поля → Page**.

## Шаблоны

- `page.tpl` — просмотр страницы;
- `page.list.tpl` — список страниц;
- `page.add.tpl` — форма добавления.
```

На странице `admin.php?m=extensions&a=details&mod=page` появится кнопка «Документация». При клике откроется страница с корректно отрендеренным Markdown.

### 20.2. Пример 2: README через библиотеку плагина

Если по каким-то причинам нельзя или не хочется создавать папку `_dir_readme_viewer` внутри расширения, положите файл в библиотеку:

```
plugins/readme_viewer/_dir_readme_viewer_list/page_README.md
```

Обратите внимание на префикс `page_` — он обязателен. Файл `README.md` без префикса в библиотеке не будет найден для модуля `page`, только для расширения с кодом пустой строки (что невозможно).

### 20.3. Пример 3: HTML-документация

Файл `plugins/contact/_dir_readme_viewer/README.html`:

```html
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Contact</title></head>
<body>
    <h1>Плагин Contact</h1>
    <p>Форма обратной связи.</p>
    <h2>Установка</h2>
    <ol>
        <li>Загрузить в <code>plugins/contact</code>.</li>
        <li>Установить через админку.</li>
    </ol>
    <script>alert('этот скрипт будет вырезан санитайзером')</script>
</body>
</html>
```

При `rv_html_mode=sanitize` тег `<script>` будет удалён, остальное отрендерится.

### 20.4. Пример 4: Plain text

Файл `plugins/contact/_dir_readme_viewer/README.txt`:

```
ПЛАГИН CONTACT
==============

Форма обратной связи.

УСТАНОВКА
---------
1. Загрузить папку contact в plugins/.
2. Установить через админку.

НАСТРОЙКИ
---------
- Email администратора
- Капча (вкл/выкл)
```

Отобразится в `<pre>` с сохранением форматирования. Все символы (в том числе `<`, `>`, `&`) будут показаны как есть.

### 20.5. Пример 5: Markdown с оглавлением

```markdown
# Документация модуля

## Содержание

1. [Возможности](#возможности)
2. [Установка](#установка)
3. [Настройка](#настройка)

## Возможности

- Возможность 1
- Возможность 2

## Установка

...

## Настройка

...
```

После рендеринга все якоря будут абсолютизированы и будут корректно работать даже при наличии `<base href>` в теме.

<a id="debugging"></a>
## 21. Отладка и диагностика

### 21.1. Включение отладки Cotonti

Включите `$cfg['debug_mode'] = true` в конфиге. При этом в `readme_viewer` появится информация о сработавших хуках.

### 21.2. Просмотр путей

Если в шаблоне используется блок отладки cotcp (см. выше), можно увидеть абсолютный путь к текущему шаблону. Это помогает понять, какой именно шаблон подхватился.

### 21.3. Проверка прав

Проверьте, что у пользователя есть права `R` на `plug/readme_viewer`. Через админку: **Права → readme_viewer**.

### 21.4. Проверка наличия файлов

Убедитесь, что:

- Папка `_dir_readme_viewer` (или `_dir_readme_viewer_list`) существует.
- Файл называется `README.md` (или `README.html`, `README.txt`, `readme.md`, `readme.html`, `readme.txt`).
- Регистр имён и расширений строгий.
- Файл читается веб-сервером (права `644` обычно достаточно).

### 21.5. Проверка ошибок PHP

Ошибки PHP пишутся в лог (зависит от конфигурации). Ищите `readme_viewer` в `error_log` или `logs/`.

<a id="troubleshooting"></a>
## 22. Диагностика проблем

### 22.1. Кнопка «Документация» не появляется

Возможные причины:

1. **Файл README не найден.** Проверьте путь и имя. Убедитесь, что регистр совпадает.
2. **Правка шаблона не внесена.** Проверьте, что в `admin.extensions.tpl` (или переопределении в теме) есть блок `<!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->`.
3. **Плагин не установлен или не активен.** Проверьте **Расширения → README Viewer**.
4. **Хук не зарегистрирован.** Проверьте `cot_plugins` в БД (записи `pl_code = 'readme_viewer'`). Если нет — переустановите плагин.

### 22.2. Кнопка есть, но страница пустая

Возможные причины:

1. **XTemplate перехвачен ядром.** Проверьте, что в `inc/readme_viewer.view.php` XTemplate назван `$rvTpl`, а не `$t`. Если назван `$t` — `system/plugin.php` перехватит его после `text('MAIN')` (который вычищает данные), повторно вызовет `parse`/`out`, и в браузере останется пустой блок.
2. **Ошибка PHP.** Проверьте `error_log`.
3. **Проблема с шаблоном.** Проверьте, что `tpl/readme_viewer.view.tpl` содержит блок `MAIN` и вложенные блоки.

### 22.3. Содержимое отображается, но оглавление не работает

Возможные причины:

1. **`id` не добавлены в заголовки.** Проверьте, что `rv_add_heading_ids()` вызывается. В текущей версии — вызывается в конце `rv_markdown_to_html()` и `rv_sanitize_html()` (через `rv_absolutize_anchors` — нет, `rv_add_heading_ids` вызывается отдельно).

   Ах, да — в текущей версии `rv_add_heading_ids` вызывается только из `rv_markdown_to_html()`. Для `.html`-файлов заголовки идут как есть (без добавления `id`). Если у вас `.html`-документация с оглавлением — нужно либо использовать `.md`, либо проставить `id` в исходном HTML.

2. **Якоря не абсолютизированы.** Проверьте `$baseUrl` — он должен быть непустым. В `inc/readme_viewer.view.php` `$rvViewUrl` строится при `$rvType !== null && $rvCode !== null`. Если URL-адрес некорректный (например, без параметров), `$rvViewUrl` будет пустым, и абсолютизация не сработает.

3. **Кириллические якоря.** Если у вас в Markdown используются латинские якоря (`#introduction`), а заголовки кириллические, то якорь не найдётся — потому что `rv_slugify` не делает транслитерацию. Используйте кириллические якоря в оглавлении (`#введение`) или переименуйте заголовки в латиницу.

### 22.4. Файл `.html` отображается как код

Проверьте настройку `rv_html_mode`. Если стоит `plain` — это ожидаемое поведение. Если хотите рендерить — поставьте `sanitize`.

### 22.5. Файл `.md` отображается как plain text

Возможные причины:

1. **Parsedown недоступен.** Проверьте, что `lib/Parsedown.php` существует и подключается. Если файла нет — используется fallback, который тоже рендерит Markdown, но с ограничениями.
2. **Неправильное расширение файла.** Если файл называется `README.MD` (заглавные) — не найдётся. Если `README.markdown` — расширение не в списке, не найдётся.

### 22.6. Ошибка 500 при заходе на страницу просмотра

Возможные причины:

1. **`$code` = null.** В старых версиях плагина `rv_is_valid_code()` принимал `string $code` — при передаче `null` падал с TypeError. В версии 1.3.3 сигнатура `?string`, поэтому `null` безопасно обрабатывается (возвращает `false`).
2. **Ошибка в шаблоне.** Проверьте синтаксис `tpl/readme_viewer.view.tpl`.
3. **Ошибка PHP.** Проверьте `error_log`.

### 22.7. HTTP-предупреждение при HTTPS

Если вы видите предупреждение «страница открыта по незащищённому HTTP-соединению», но уверены, что используете HTTPS — проверьте:

1. Значение `Cot::$sys['secure']` (должно быть `true`).
2. Значение `Cot::$sys['host']`. Если хост — IP-адрес или нестандартный домен, и он не `localhost`/`127.0.0.1`, предупреждение появится.

<a id="faq"></a>
## 23. FAQ

### 23.1. Можно ли отключить предупреждение HTTPS?

Только отключив жёсткий режим (`rv_enforce_https=0`) — но тогда при HTTP файл будет читаться, а не блокироваться. Предупреждение в мягком режиме остаётся.

### 23.2. Можно ли использовать латинские якоря в Markdown?

С текущей реализацией `rv_slugify` — только если заголовки тоже латинские. Для кириллических заголовков слаг будет кириллическим.

### 23.3. Можно ли показать документацию для неустановленного расширения?

В текущей версии — нет. Хук `admin.extensions.details` вызывается только когда расширение установлено и открыта страница его деталей. Если расширение не установлено (папка есть, но записи в `cot_core` нет) — страница деталей отдаст 404.

### 23.4. Можно ли ограничить размер файла без переустановки плагина?

Да, значение `rv_max_size` хранится в БД (`cot_config`). Можно изменить через админку: **Расширения → README Viewer → Настройки**. Или напрямую в БД: `UPDATE cot_config SET config_value = '2097152' WHERE config_owner = 'plug' AND config_cat = 'readme_viewer' AND config_name = 'rv_max_size'`.

### 23.5. Почему Parsedown, а не CommonMark?

Parsedown — лёгкая библиотека (~50KB), быстрая, с safe mode из коробки. CommonMark (например, `league/commonmark`) — более функциональная, но требует Composer-установки и весит больше. Для документации расширений Parsedown более чем достаточно.

### 23.6. Что делать, если README больше 5 MiB?

Увеличить `rv_max_size` через БД (значения `select` в настройках не покрывают больше 5 MiB). Например: `UPDATE cot_config SET config_value = '10485760' WHERE ...`.

### 23.7. Можно ли отключить санитайзинг HTML полностью?

Нет, это осознанное ограничение безопасности. Если нужна полная свобода HTML — используйте режим `plain` (показ как код). Это безопасно, но не рендерится.

### 23.8. Работает ли плагин с темой, у которой нет Bootstrap 5?

CSS плагина использует классы `.alert`, `.alert-info`, `.alert-secondary` (из Bootstrap). Если тема не подключает Bootstrap, эти блоки будут отображаться без стилей, но функционально. Основной контент (`.rv-body`) стилизован собственным CSS, не зависящим от Bootstrap.

### 23.9. Можно ли встраивать плагин в другой плагин?

Технически — да, через `cot_incfile('readme_viewer', 'plug')` и вызов функций. Но учтите, что некоторые функции (`rv_markdown_to_html`) используют `Cot::$cfg['plugins_dir']`, что может быть неинтуитивно.

### 23.10. Плагин совместим с Cotonti v0.9.x?

Да. В коде используются стандартные API (`cot_auth`, `cot_url`, `cot_import`, `XTemplate`, `cot_langfile`, `cot_incfile`), доступные в обеих ветках. `Cot::` статический фасад тоже доступен в обеих.

<a id="removal"></a>
## 24. Удаление плагина

### 24.1. Полное удаление

1. **Админка → Расширения → README Viewer → Удалить**. Ядро само удалит:
   - Записи в `cot_plugins` (привязки хуков).
   - Записи в `cot_config` (настройки).
   - Записи в `cot_auth` (права).
   - Записи в `cot_core` (метаданные).
2. Удалить папку `plugins/readme_viewer/` по FTP.
3. Удалить фрагмент кнопки из `admin.extensions.tpl` (или из переопределения в теме).
4. Удалить папки `_dir_readme_viewer` внутри расширений (если они больше не нужны).
5. **Админка → Кэш → Очистить**.

### 24.2. Что НЕ удаляется автоматически

- Файлы плагина (папка `plugins/readme_viewer/`).
- Правка шаблона `admin.extensions.tpl`.
- Папки `_dir_readme_viewer` внутри расширений.
- Файлы в `_dir_readme_viewer_list/` (удаляются вместе с папкой плагина).

### 24.3. Отключение без удаления

Если хотите временно отключить плагин: **Расширения → README Viewer → Приостановить**. Страница просмотра перестанет открываться (404 или 403 в зависимости от того, как ядро обработает неактивный плагин).

<a id="license"></a>
## 25. Лицензия

Плагин распространяется свободно с сохранением Copyright (c) 2026 webitproff.

- Репозиторий: https://github.com/webitproff/readme_viewer-cotonti
- Поддержка: https://abuyfile.com/forums/cotonti/custom/plugs/
- Маркетплейс: https://abuyfile.com/ru/market/cotonti/plugs/readme_viewer


Готовый README сохраняется как `README.md` в `_dir_readme_viewer/README.md` внутри самого плагина — тогда он будет отображаться при клике на кнопку «Документация» для самого плагина `readme_viewer` как и любого другого с данным плагином.
