<?php
/* ====================
[BEGIN_COT_EXT]
Code=readme_viewer
Name=README Viewer
Description=
Version=1.3.3
Date=2026-11-21
Author=webitproff
Copyright=(c) webitproff 2026 | https://github.com/webitproff/
Notes=
Auth_guests=0
Lock_guests=RW12345A
Auth_members=0
Lock_members=RW12345A
Requires_modules=
Requires_plugins=
Recommends_modules=
Recommends_plugins=
[END_COT_EXT]

[BEGIN_COT_EXT_CONFIG]
rv_enforce_https=00:radio:0:1:1:
rv_html_mode=01:select:sanitize,plain:
rv_max_size=02:select:1048576,2097152,3145728,5242880:
[END_COT_EXT_CONFIG]
==================== */


/**
 * readme_viewer.setup.php - Register data in $db_core, $db_config, $db_auth, $db_plugins.
 * Setup & Config File for the Plugin readme_viewer for module page
 *
 * readme_viewer plugin for Cotonti v1.+, PHP 8.5+, MySQL 8.4
 *
 * ReadMeMore:       https://abuyfile.com/ru/market/cotonti/plugs/readme_viewer
 * Support:          https://abuyfile.com/forums/cotonti/custom/plugs/
 * Source:           https://github.com/webitproff/readme_viewer-cotonti
 *
 * Date: 2026-11-21
 * @package readme_viewer
 * @version 1.3.3
 * @author webitproff
 * @copyright Copyright (c) webitproff 2026 | https://github.com/webitproff/readme_viewer-cotonti
 * @license BSD
 */

/**
 * File: plugins/readme_viewer/readme_viewer.setup.php
 *
 * Метаданные плагина readme_viewer. Читаются ядром через cot_infoget()
 * при установке (system/extensions.php → cot_extension_install()).
 *
 * ─ Code ────────────────────────────────────────────────────────────────
 *   Уникальный код плагина. Совпадает с именем папки plugins/readme_viewer.
 *   Используется как auth_option при проверке прав (cot_auth('plug', 'readme_viewer'))
 *   и как имя расширения в cot_url('readme_viewer', ...).
 *
 * ─ Auth_guests / Auth_members = 0 ──────────────────────────────────────
 *   На момент установки у всех групп, кроме суперадминов, прав на плагин нет.
 *   cot_extension_install() сам выдаст полные права (255 бит) только группе
 *   COT_GROUP_SUPERADMINS. Остальным группам разрешения назначает админ
 *   через Админку → Права (ACL по plug/readme_viewer).
 *
 * ─ Lock_guests / Lock_members = RW12345A ───────────────────────────────
 *   Все биты заблокированы от изменения группами с уровнем ниже суперадмина.
 *   Это стандартная практика Cotonti: права по плагину меняет только
 *   суперадмин через админку.
 *
 * ─ [COT_EXT_CONFIG] ────────────────────────────────────────────────────
 *   rv_enforce_https (radio 0/1):
 *     0 — мягкий режим: при HTTP показываем предупреждение, но файл читаем
 *         (необходимо для локальной разработки);
 *     1 — жёсткий режим: при HTTP файл не читаем вовсе, только предупреждение.
 *
 *   rv_html_mode (select sanitize/plain):
 *     sanitize — прогоняем .html-документацию через DOMDocument-санитайзер
 *                с белым списком тегов и атрибутов;
 *     plain    — показываем .html как исходный код в <pre> (безопасно,
 *                но не рендерится браузером).
 *
 *   rv_max_size (string):
 *     Предел размера файла в байтах. 1048576 = 1 МБ. Защита от попытки
 *     прочитать и отрендерить многомегабайтный README и уронить PHP.
 */