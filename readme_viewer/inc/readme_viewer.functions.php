<?php
/**
 * File: plugins/readme_viewer/inc/readme_viewer.functions.php
 *
 * Публичные функции плагина. Только данные — никакого HTML.
 *
 * Безопасность построена по образцу cot_readraw() из модуля page:
 *   - realpath() базовой директории и кандидата;
 *   - строгая проверка strpos($real, $base . DIRECTORY_SEPARATOR) === 0
 *     (отсекает и «..» traversal, и симлинки за пределы папки);
 *   - белый список расширений;
 *   - никаких пользовательских путей в URL — только code и type,
 *     прошедшие регулярку;
 *   - предел размера файла из конфига.
 */
defined('COT_CODE') or die('Wrong URL.');

/**
 * Проверка кода расширения.
 *
 * Правила:
 *   - непустой;
 *   - длина ≤ 64;
 *   - только латиница, цифры, дефис, подчёркивание;
 *   - первый символ — буква (защита от чисто числовых «кодов»,
 *     которые могли бы выглядеть как путь или id).
 *
 * Возвращает true/false.
 */
function rv_is_valid_code(?string $code): bool
{
    if ($code === null || $code === '' || mb_strlen($code) > 64) {
        return false;
    }
    return (bool) preg_match('/^[a-z][a-z0-9_\-]*$/i', $code);
}

/**
 * Возвращает URL страницы просмотра README либо null, если файл не найден.
 *
 * Используется в хуке admin.extensions.details: если null — кнопка
 * не выводится. Единственная точка, где плагин решает,
 * «есть ли что показывать» для конкретного расширения.
 */
function rv_build_documentation_url(?string $code, bool $isModule): ?string
{
    if (!rv_is_valid_code($code)) {
        return null;
    }
    if (rv_find_documentation($code, $isModule) === null) {
        return null;
    }
    return cot_url('readme_viewer', [
        'm'    => 'view',
        'type' => $isModule ? 'module' : 'plug',
        'code' => $code,
    ]);
}

/**
 * Поиск файла README по приоритетам.
 *
 * Приоритет 1:
 *   <расширение>/_dir_readme_viewer/{README|readme}.{md|html|txt}
 *   — папку _dir_readme_viewer администратор создаёт вручную по FTP
 *     внутри папки расширения и кладёт туда README.md (или .html/.txt).
 *   — Ищем в modules_dir для модуля и plugins_dir для плагина.
 *
 * Приоритет 2:
 *   plugins/readme_viewer/_dir_readme_viewer_list/{code}_{README|readme}.{md|html|txt}
 *   — централизованная библиотека README-файлов в нашем плагине.
 *   — Имя файла с префиксом кода расширения:
 *     page_README.md, contact_readme.html и т.п.
 *
 * Возвращает ['path' => realpath, 'ext' => 'md'|'html'|'txt'] либо null.
 *
 * Проверка «папка расширения существует» стоит до первого поиска:
 * если расширения физически нет (было удалено вручную, а из БД
 * не вычищено), нет смысла лезть в файловую систему — сразу null.
 */
function rv_find_documentation(?string $code, bool $isModule): ?array
{
    if (!rv_is_valid_code($code)) {
        return null;
    }

    // Папка расширения. modules_dir / plugins_dir берутся из конфига Cotonti.
    $extDir = ($isModule ? Cot::$cfg['modules_dir'] : Cot::$cfg['plugins_dir']) . '/' . $code;
    if (!is_dir($extDir)) {
        return null;
    }

    // Приоритет 1: папка внутри самого расширения.
    $hit = rv_probe_dir($extDir . '/_dir_readme_viewer', ['README', 'readme']);
    if ($hit !== null) {
        return $hit;
    }

    // Приоритет 2: библиотека внутри нашего плагина.
    $libDir = Cot::$cfg['plugins_dir'] . '/readme_viewer/_dir_readme_viewer_list';
    $hit = rv_probe_dir($libDir, [$code . '_README', $code . '_readme']);
    if ($hit !== null) {
        return $hit;
    }

    return null;
}

/**
 * Безопасный перебор (имя, расширение) в директории.
 *
 * Правила:
 *   - имена строго в заданном регистре: README или readme
 *     (никаких ReadMe, README2 и т.п. — совпадает с требованием
 *     игнорировать прочий регистр);
 *   - расширения строго в порядке md → html → txt (заданный приоритет);
 *   - реальный путь файла обязан лежать строго ВНУТРИ $baseReal
 *     (защита от path traversal через ".." и от symlink-escape);
 *   - файл должен быть читаемым;
 *   - реальное расширение файла — тоже из белого списка.
 *
 * Возвращает ['path' => string, 'ext' => 'md'|'html'|'txt'] либо null.
 */
function rv_probe_dir(string $dir, array $baseNames): ?array
{
    // realpath раскрывает симлинки и нормализует путь.
    $baseReal = realpath($dir);
    if ($baseReal === false || !is_dir($baseReal)) {
        return null;
    }

    $extensions = ['md', 'html', 'txt'];

    foreach ($baseNames as $name) {
        foreach ($extensions as $ext) {
            $candidate = $baseReal . DIRECTORY_SEPARATOR . $name . '.' . $ext;
            if (!is_file($candidate)) {
                continue;
            }

            // realpath кандидата. Если файла нет (гонка) — realpath вернёт false.
            $real = realpath($candidate);
            if ($real === false) {
                continue;
            }

            // Ключевая защита: реальный путь файла должен начинаться
            // с $baseReal + разделитель. Отсекает:
            //   - относительные пути вида ../../etc/passwd;
            //   - симлинки, указывающие за пределы папки расширения.
            if (strpos($real, $baseReal . DIRECTORY_SEPARATOR) !== 0) {
                continue;
            }

            if (!is_readable($real)) {
                continue;
            }

            // Дополнительная страховка: реальное расширение
            // должно быть из белого списка.
            $realExt = strtolower(pathinfo($real, PATHINFO_EXTENSION));
            if (!in_array($realExt, $extensions, true)) {
                continue;
            }

            return ['path' => $real, 'ext' => $realExt];
        }
    }

    return null;
}
/**
 * Безопасное чтение найденного файла.
 *
 * Возвращает:
 *   [
 *     'content' => ?string,   // содержимое файла или null при ошибке
 *     'ext'     => string,    // 'md' | 'html' | 'txt'
 *     'path'    => string,    // абсолютный реальный путь (realpath)
 *     'error'   => ?string,   // null | 'too_large' | 'read_failed'
 *   ]
 *
 * error:
 *   too_large    — размер файла больше rv_max_size;
 *   read_failed  — file_get_contents() вернул false (нет прав или I/O).
 *
 * null — если файл не найден вообще (rv_find_documentation вернул null).
 *
 * Поле 'path' — абсолютный путь, полученный через realpath() внутри
 * rv_probe_dir(). Используется во view для вывода относительного пути
 * и имени файла в шаблоне (см. inc/readme_viewer.view.php).
 *
 * Предел размера из конфига защищает от попытки прочитать гигабайтный
 * «README» и уронить PHP по памяти. 1 МБ по умолчанию — более чем достаточно
 * для нормальной документации расширения.
 * rv_max_size задаётся в БАЙТАХ и сравнивается напрямую с filesize().
 * Ориентиры (1 MiB = 1024×1024):
 *     1 МБ  → 1048576
 *     2 МБ  → 2097152
 *     3 МБ  → 3145728
 *     5 МБ  → 5242880
 * При rv_max_size = 0 (или не задан) проверка размера ОТКЛЮЧЕНА —
 * «1 МБ по умолчанию» в этом коде НЕ реализован.
 */
function rv_read_documentation(?string $code, bool $isModule): ?array
{
    $found = rv_find_documentation($code, $isModule);
    if ($found === null) {
        return null;
    }

    $maxSize = (int) (Cot::$cfg['plugin']['readme_viewer']['rv_max_size'] ?? 0);

    if ($maxSize > 0 && filesize($found['path']) > $maxSize) {
        return [
            'content' => null,
            'ext'     => $found['ext'],
            'path'    => $found['path'],
            'error'   => 'too_large',
        ];
    }

    $content = file_get_contents($found['path']);
    if ($content === false) {
        return [
            'content' => null,
            'ext'     => $found['ext'],
            'path'    => $found['path'],
            'error'   => 'read_failed',
        ];
    }

    return [
        'content' => $content,
        'ext'     => $found['ext'],
        'path'    => $found['path'],
        'error'   => null,
    ];
}