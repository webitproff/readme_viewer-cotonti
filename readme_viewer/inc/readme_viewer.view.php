<?php
/**
 * File: plugins/readme_viewer/inc/readme_viewer.view.php
 *
 * Исполнитель режима view. Задачи:
 *   1) провалидировать type/code из URL;
 *   2) применить HTTPS-политику (жёсткий/мягкий режим);
 *   3) безопасно прочитать файл через rv_read_documentation();
 *   4) отрендерить XTemplate, подставив готовый HTML.
 *
 * Никакой бизнес-логики поиска/чтения здесь нет — она в functions.php.
 * Здесь только выбор способа отображения по расширению файла
 * и формирование $pluginBody, который system/plugin.php подставит
 * в стандартный контейнер (header.php → $pluginBody → footer.php).
 *
 * ВАЖНО: переменная шаблона названа $rvTpl, а не $t.
 * system/plugin.php в конце делает:
 *
 *     $pluginTemplate = $t ?? null;
 *     require_once Cot::$cfg['system_dir'] . '/header.php';
 *     $t = $pluginTemplate;
 *     if (isset($t) && is_object($t)) { $t->parse('MAIN'); $t->out('MAIN'); }
 *     else { echo $pluginBody; }
 *
 * Если наш XTemplate назвать $t, ядро перехватит его после `text('MAIN')`,
 * повторно вызовет parse()/out() — но данные уже вычищены text() — и
 * в браузере останется пустой блок. Поэтому используем $rvTpl.
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

// Конвертер Markdown и санитайзер HTML.
require_once cot_incfile('readme_viewer', 'plug', 'markdown');

// type из URL. 'ALP' в cot_import отсекает всё, кроме [A-Za-z0-9_-],
// ещё до нашей регулярки.
$rvType = cot_import('type', 'G', 'ALP');
$rvCode = cot_import('code', 'G', 'ALP');

// module → modules_dir, иначе → plugins_dir.
$rvIsModule = ($rvType === 'module');

// Шаблон. cot_tplfile сам ищет в теме пользователя, затем в плагине
// (plugins/readme_viewer/tpl/), затем fallback — тоже туда.
$rvTpl = new XTemplate(cot_tplfile('readme_viewer.view', 'plug'));

// Базовый URL страницы просмотра — для якорей внутри документации.
// Cotonti ставит <base href="..."> в шапке темы, и браузер резолвит
// "#anchor" относительно base, а не текущего URL. Это уводит клик
// на чужую страницу без параметров плагина. После рендера Markdown/HTML
// заменяем href="#anchor" на href="<полный URL страницы>#anchor".
$rvViewUrl = '';
if ($rvType !== null && $rvCode !== null) {
    $rvViewUrl = Cot::$sys['abs_url'] . cot_url(
        'readme_viewer',
        [
            'm'    => 'view',
            'type' => $rvIsModule ? 'module' : 'plug',
            'code' => $rvCode,
        ],
        '',
        true
    );
}

// ─ HTTPS-политика ──────────────────────────────────────────────────────
// rv_enforce_https = 0 (мягкий): предупреждение + показ файла.
// rv_enforce_https = 1 (жёсткий): при HTTP файл не читаем вовсе.
// localhost / 127.0.0.1 всегда считаем защищёнными — иначе локальная
// разработка была бы невозможна.
$rvEnforce  = !empty(Cot::$cfg['plugin']['readme_viewer']['rv_enforce_https']);
$rvInsecure = empty(Cot::$sys['secure'])
    && !in_array(Cot::$sys['host'], ['localhost', '127.0.0.1'], true);

if ($rvInsecure) {
    $rvTpl->assign('RV_INSECURE_WARNING', Cot::$L['rv_insecure_warning']);
    $rvTpl->parse('MAIN.RV_INSECURE');

    if ($rvEnforce) {
        // Жёсткий режим: файл не читаем, показываем только предупреждение.
        $rvTpl->parse('MAIN');
        $pluginBody = $rvTpl->text('MAIN');
        return;
    }
}

// ─ Валидация code ─────────────────────────────────────────────────────
if (!rv_is_valid_code($rvCode)) {
    // Кривой code — фактически «документации нет».
    $rvTpl->parse('MAIN.RV_NOT_FOUND');
} else {
    $rvDoc = rv_read_documentation($rvCode, $rvIsModule);

    if ($rvDoc === null) {
        // Файла нет ни по одному приоритету.
        $rvTpl->parse('MAIN.RV_NOT_FOUND');
    } elseif (!empty($rvDoc['error'])) {
        // Ошибка чтения/размера — локализованный текст.
        $rvTpl->assign(
            'RV_ERROR_TEXT',
            Cot::$L['rv_error_' . $rvDoc['error']] ?? Cot::$L['rv_error_read_failed']
        );
        $rvTpl->parse('MAIN.RV_ERROR');
    } else {
        $rvExt = $rvDoc['ext'];
        $rvRaw = (string) $rvDoc['content'];
        $rvHtml = '';

        if ($rvExt === 'txt') {
            // .txt — только в <pre>, полностью экранируем.
            $rvHtml = '<pre class="rv-pre">'
                . htmlspecialchars($rvRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                . '</pre>';
        } elseif ($rvExt === 'md') {
            // .md — безопасный конвертер (Parsedown safe mode либо fallback).
            $rvHtml = rv_markdown_to_html($rvRaw, $rvViewUrl);
        } else {
            // .html — на выбор: санитайзинг (белый список) или исходник.
            $rvMode = Cot::$cfg['plugin']['readme_viewer']['rv_html_mode'] ?? 'sanitize';
            $rvHtml = ($rvMode === 'plain')
                ? '<pre class="rv-pre">'
                    . htmlspecialchars($rvRaw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '</pre>'
                : rv_sanitize_html($rvRaw, $rvViewUrl);
        }

        // Заголовок страницы и вкладки.
        Cot::$out['subtitle'] = Cot::$L['rv_open_doc_short'] . ': ' . $rvCode;

        // Относительный путь к файлу — от корня сайта.
        // Realpath: E:\...\public_html\plugins\page\_dir_readme_viewer\README.md
        // baseDir:  E:\...\public_html
        // → plugins/page/_dir_readme_viewer/README.md
        $rvFilePath = '';
        if (!empty($rvDoc['path'])) {
            $rvBase = rtrim(Cot::$sys['baseDir'], '/\\');
            $rvFilePath = str_replace('\\', '/', $rvDoc['path']);
            $rvFilePath = str_replace(
                str_replace('\\', '/', $rvBase) . '/',
                '',
                $rvFilePath
            );
        }

        $rvTpl->assign([
            'RV_TITLE'     => htmlspecialchars($rvCode, ENT_QUOTES, 'UTF-8'),
            'RV_CONTENT'   => $rvHtml,
            'RV_FILE_PATH' => htmlspecialchars($rvFilePath, ENT_QUOTES, 'UTF-8'),
            'RV_FILE_NAME' => htmlspecialchars(basename($rvDoc['path']), ENT_QUOTES, 'UTF-8'),
            'RV_FILE_EXT'  => htmlspecialchars($rvExt, ENT_QUOTES, 'UTF-8'),
        ]);
        $rvTpl->parse('MAIN.RV_OK');
    }
}

// Стандартный механизм вывода сообщений Cotonti (ошибки, warnings).
cot_display_messages($rvTpl);

$rvTpl->parse('MAIN');
$pluginBody = $rvTpl->text('MAIN');