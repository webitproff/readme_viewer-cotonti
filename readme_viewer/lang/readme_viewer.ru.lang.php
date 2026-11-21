<?php
/**
 * File: plugins/readme_viewer/lang/readme_viewer.ru.lang.php
 *
 * Русская локализация плагина readme_viewer.
 * Ядро подхватывает её автоматически через cot_langfile('readme_viewer', 'plug')
 * при Cot::$usr['lang'] === 'ru'.
 *
 * ─ info_* ──────────────────────────────────────────────────────────────
 *   Используются в админке при отображении карточки плагина
 *   (см. cot_get_extensionparams() в system/admin/admin.functions.php).
 *
 * ─ cfg_* ──────────────────────────────────────────────────────────────
 *   Заголовки и параметры полей конфига из setup-файла. Cotonti сам
 *   подставит их в форму настроек плагина.
 *
 * ─ rv_* ───────────────────────────────────────────────────────────────
 *   Строки, которые использует фронт (кнопка в админке, страница просмотра).
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

$L['info_name']  = 'README Viewer';
$L['info_desc']  = 'Позволяет стандартизировать документацию для расширений и обеспечивает супер-амдинистратору безопасный просмотр README-файлов расширений по ссылке из админ-панели во фронтенде.';
$L['info_notes'] = 'Ищет README.md / README.html / README.txt в папке расширения (_dir_readme_viewer), затем в библиотеке плагина (_dir_readme_viewer_list). Добавляет кнопку «Документация» на страницу расширения в админке.';

$L['readme_viewer_title'] = $L['info_name'];
$L['readme_viewer_desc']  = $L['info_desc'];
$L['readme_viewer_name']  = $L['info_name'];


$L['rv_open_doc']          = 'Открыть документацию расширения';
$L['rv_open_doc_short']    = 'Документация';
$L['rv_not_found']         = 'Файл документации не найден.';
$L['rv_insecure_warning']  = 'Внимание: страница открыта по незащищённому HTTP-соединению.';
$L['rv_error_too_large']   = 'Файл документации слишком большой для отображения.';
$L['rv_error_read_failed'] = 'Не удалось прочитать файл документации.';

$L['cfg_rv_enforce_https']        = 'Запрещать просмотр по HTTP';
$L['cfg_rv_enforce_https_hint']   = ''
    . '0 (НЕТ) — мягкий режим: при HTTP показываем предупреждение, но файл читаем <br>'
	. '(необходимо для локальной разработки) <br>'
    . '<hr>'
    . '1 (ДА)— жёсткий режим: при HTTP файл не читаем вовсе, только предупреждение.'
    . '';
	
	
$L['cfg_rv_html_mode']            = 'Режим отображения HTML-документации';
$L['cfg_rv_html_mode_params']     = ['sanitize' => 'Санитайзинг (белый список)', 'plain' => 'Как исходный код'];


$L['cfg_rv_max_size']        = 'Максимальный размер файла, байт';
$L['cfg_rv_max_size_hint']   = ''
    . 'Предел размера файла в байтах. 1048576 = 1 МБ. <br>'
	. 'Защита от попытки прочитать и отрендерить многомегабайтный README и уронить PHP. <br>'
    . '1048576 байт (примерно 1 Mb) <br>'
    . '2097152 байт (примерно 2 Mb) <br>'
    . '3145728 байт (примерно 3 Mb) <br>'
    . '5242880 байт (примерно 5 Mb)'
    . '';
	
/* с цифрами не работает
	$L['cfg_rv_max_size_params'] = [
    '1048576' => '1048576 байт (примерно 1 Mb)',
    '2097152' => '2097152 байт (примерно 2 Mb)',
    '3145728' => '3145728 байт (примерно 3 Mb)',
    '5242880' => '5242880 байт (примерно 5 Mb)'
]; 
 */