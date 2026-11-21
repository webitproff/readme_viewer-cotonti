<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=standalone
[END_COT_EXT]
==================== */

/**
 * File: plugins/readme_viewer/readme_viewer.php
 *
 * Standalone-точка входа плагина. Ядро подключает этот файл при запросе
 * index.php?e=readme_viewer (system/plugin.php).
 *
 * Плагин выступает «роутером» по параметру m, по аналогии с messenger.php.
 * Единственный публичный режим — view (просмотр README-файла расширения).
 *
 * Параметры URL, ожидаемые режимом view:
 *   type = module | plug  — тип расширения (mod= или pl= в админке);
 *   code = <extension>    — код расширения (page, contact, users, forums, …).
 *
 * Примеры URL, которые открывает кнопка «Документация»:
 *   index.php?e=readme_viewer&m=view&type=module&code=page
 *   index.php?e=readme_viewer&m=view&type=plug&code=contact
 *
 * Любое значение m, кроме 'view', принудительно сводится к 'view' —
 * это защита от попытки подключить несуществующий inc-файл через m=
 * (по аналогии с тем, как это сделано в messenger.php).
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

defined('COT_CODE') or die('Wrong URL.');

// Явно помечаем location — иногда нужно для логов и ядровых проверок.
Cot::$env['location'] = 'readme_viewer';

// Функции плагина (rv_is_valid_code, rv_build_documentation_url,
// rv_find_documentation, rv_probe_dir, rv_read_documentation).
require_once cot_incfile('readme_viewer', 'plug');

// Языковой файл (ru/en — cot_langfile сам подберёт по Cot::$usr['lang']).
require_once cot_langfile('readme_viewer', 'plug');

// Проверяем ACL плагина. По setup-файлу доступ есть только у суперадминов.
// cot_block() при отсутствии R делает редирект на 403 Forbidden.
list($usr['auth_read'], $usr['auth_write'], $usr['isadmin']) = cot_auth('plug', 'readme_viewer');
cot_block($usr['auth_read']);

// Разбор режима. Неизвестное → принудительно view.
$m = cot_import('m', 'G', 'ALP');
if ($m !== 'view') {
    $m = 'view';
}

// Подключаем исполнителя режима — inc/readme_viewer.view.php.
// Именно он сформирует $pluginBody, который system/plugin.php
// вставит в стандартный контейнер (header.php → $pluginBody → footer.php).
require_once cot_incfile('readme_viewer', 'plug', $m);