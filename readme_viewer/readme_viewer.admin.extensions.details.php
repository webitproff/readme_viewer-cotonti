<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=admin.extensions.details
Order=10
[END_COT_EXT]
==================== */

/**
 * File: plugins/readme_viewer/readme_viewer.admin.extensions.details.php
 *
 * Хук admin.extensions.details. Срабатывает в system/admin/admin.extensions.php
 * внутри case 'details', непосредственно перед $t->parse('MAIN.DETAILS'):
 *
 *     foreach (cot_getextplugins('admin.extensions.details') as $pl) {
 *         include $pl;
 *     }
 *
 *     $t->parse('MAIN.DETAILS');
 *
 * В области видимости хука гарантированно есть:
 *   $code      — код расширения (page, contact, users, forums, …)
 *   $is_module — true для модуля, false для плагина
 *   $type      — 'module' | 'plug' (то же, что $is_module, но строкой)
 *   $t         — XTemplate страницы admin.extensions
 *
 * Задача хука — назначить теги кнопки «Документация» для блока DETAILS
 * шаблона admin.extensions.tpl. Никакого JS, никаких правок ядровой
 * логики. Однократная правка шаблона нужна только для добавления
 * фрагмента (см. блок «ФРАГМЕНТ ДЛЯ ШАБЛОНА» в конце этого файла).
 *
 * Если README у расширения нет ни по одному приоритету —
 * rv_build_documentation_url() вернёт null, ADMIN_EXTENSIONS_HAS_DOC
 * будет false, кнопка не отрендерится.
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

// Страховка от прямого вызова файла вне контекста хука.
if (empty($code) || !isset($is_module)) {
    return;
}

// Функции плагина (rv_build_documentation_url и вся безопасная логика).
require_once cot_incfile('readme_viewer', 'plug');

// Языковые строки (rv_open_doc, rv_open_doc_short).
require_once cot_langfile('readme_viewer', 'plug');

// null = файл не найден, кнопка не выводится.
$rvUrl = rv_build_documentation_url($code, (bool) $is_module);

// Назначаем теги для шаблона DETAILS.
// Имена с префиксом ADMIN_EXTENSIONS_, чтобы не пересекаться с другими плагинами.
$t->assign([
    'ADMIN_EXTENSIONS_HAS_DOC'   => ($rvUrl !== null),
    'ADMIN_EXTENSIONS_DOC_URL'   => $rvUrl ?? '',
    'ADMIN_EXTENSIONS_DOC_TITLE' => Cot::$L['rv_open_doc'],
    'ADMIN_EXTENSIONS_DOC_LABEL' => Cot::$L['rv_open_doc_short'],
]);

/* =========================================================================
 * ФРАГМЕНТ ДЛЯ ШАБЛОНА (однократная правка)
 * =========================================================================
 *
 * Файл: system/admin/tpl/admin.extensions.tpl
 * (или, правильнее — переопределение в теме:
 *  themes/admin/{admintheme}/admin.extensions.tpl,
 *  чтобы правка не потерялась при обновлении ядра).
 *
 * В блоке DETAILS, внутрь <div class="button-toolbar">,
 * добавить ПОСЛЕ последней штатной кнопки (Structure), перед </div>:
 *
 *     <!-- IF {ADMIN_EXTENSIONS_STRUCTURE_URL} -->
 *     <a title="{PHP.L.Structure}" href="{ADMIN_EXTENSIONS_STRUCTURE_URL}" class="button large">{PHP.L.Structure}</a>
 *     <!-- ENDIF -->
 *
 *     <!-- IF {ADMIN_EXTENSIONS_HAS_DOC} -->
 *     <a title="{ADMIN_EXTENSIONS_DOC_TITLE}"
 *        href="{ADMIN_EXTENSIONS_DOC_URL}"
 *        class="button large"
 *        target="_blank"
 *        rel="noopener noreferrer">{ADMIN_EXTENSIONS_DOC_LABEL}</a>
 *     <!-- ENDIF -->
 * </div>
 * <!-- ENDIF -->
 */