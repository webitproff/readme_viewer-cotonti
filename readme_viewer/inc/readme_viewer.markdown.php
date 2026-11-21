<?php
/**
 * File: plugins/readme_viewer/inc/readme_viewer.markdown.php
 *
 * Конвертер Markdown → HTML (безопасный) и санитайзер HTML-документации.
 *
 * Принципы:
 *   - Parsedown подключается напрямую из plugins/readme_viewer/lib/Parsedown.php.
 *     Если файл отсутствует или класс не загрузился — используется
 *     минимальный fallback: сначала htmlspecialchars() на всём тексте,
 *     потом очень ограниченная разметка. Исполняемых тегов появиться
 *     не может, потому что вход уже экранирован.
 *
 * Для .html-документации используем DOMDocument с белым списком тегов
 * и атрибутов. Это надёжнее, чем регулярки.
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

/**
 * Markdown → HTML (безопасно).
 *
 * Parsedown подключается напрямую из plugins/readme_viewer/lib/Parsedown.php.
 * Safe mode:
 *   - не рендерит <script>, <iframe>, <object>, <embed>;
 *   - фильтрует javascript: и data: в ссылках;
 *   - вырезает on* атрибуты;
 *   - setMarkupEscaped(true) — не пропускает произвольный HTML из исходника;
 *   - setMarkupEscaped(false) — пропускает безопасный inline/block HTML
 *     из исходника (например, <a id="anchor"></a> для явных якорей);
 *   - после рендеринга HTML прогоняется через rv_sanitize_html() —
 *     DOMDocument-санитайзер с белым списком тегов и атрибутов.
 *     Блокирует <script>, <iframe>, <object>, <embed>, on* атрибуты,
 *     javascript: и data: в ссылках, пропускает безопасные теги
 *     (<a id="...">, <span>, <div> и т.п.) из белого списка;
 *   - setBreaksEnabled(false) — одиночный \n внутри абзаца не превращается
 *     в <br> (иначе Markdown-списки рендерятся с лишними переносами).
 *
 * Fallback (если lib/Parsedown.php отсутствует или не загрузился):
 * минимальный парсер, но безопасный — сначала htmlspecialchars(),
 * потом простая разметка.
 */
function rv_markdown_to_html(string $md, string $baseUrl = ''): string
{
    // Прямое подключение Parsedown из папки плагина.
    if (!class_exists('Parsedown')) {
        $rvParsedownFile = Cot::$cfg['plugins_dir'] . '/readme_viewer/lib/Parsedown.php';
        if (is_file($rvParsedownFile) && is_readable($rvParsedownFile)) {
            require_once $rvParsedownFile;
        }
    }

    if (class_exists('Parsedown')) {
        $rvParser = new Parsedown();
        // safeMode ВЫКЛ: при safeMode(true) Parsedown блокирует
        // ЛЮБОЙ inline HTML в Markdown (inlineMarkup/blockMarkup
        // возвращают null), и <a id="anchor"></a> превращается в текст.
        if (method_exists($rvParser, 'setSafeMode')) {
            $rvParser->setSafeMode(false);
        }
        if (method_exists($rvParser, 'setMarkupEscaped')) {
            $rvParser->setMarkupEscaped(false);
        }
        if (method_exists($rvParser, 'setBreaksEnabled')) {
            $rvParser->setBreaksEnabled(false);
        }
        $rvHtml = $rvParser->text($md);
        $rvHtml = rv_add_heading_ids($rvHtml);
        // Пропускаем через DOMDocument-санитайзер — блокирует
        // <script>, <iframe>, <object>, <embed>, on* атрибуты,
        // javascript: и data: в ссылках. Одновременно разрешает
        // безопасный inline HTML (<a id="...">, <span>, <div> и т.д.)
        // из белого списка.
        return rv_sanitize_html($rvHtml, $baseUrl);
    }

    // ─ Fallback: минимальный парсер ────────────────────────────────────
    // Нормализуем переносы строк до \n (Windows CRLF → LF).
    // Без этого `^...$` в /m-режиме захватывает \r, и получается
    // <h1>Header<br></h1> — кривой HTML с <br> внутри заголовка.
    $md = str_replace(["\r\n", "\r"], "\n", $md);

    // Экранируем ВСЁ до разметки — гарантия отсутствия инъекций.
    $s = htmlspecialchars($md, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    // Fenced code: ```lang ... ``` → <pre><code>.
    $s = preg_replace_callback(
        '/```[a-zA-Z0-9_\-]*\n(.*?)```/s',
        static fn(array $m): string => '<pre class="rv-code"><code>' . $m[1] . '</code></pre>',
        $s
    );

    // Inline code: `code`.
    $s = preg_replace('/`([^`\n]+)`/', '<code>$1</code>', $s);

    // Заголовки. Порядок — от длинных к коротким,
    // чтобы ###### не съедалось правилом для #.
    $s = preg_replace('/^######\s+(.+)$/m', '<h6>$1</h6>', $s);
    $s = preg_replace('/^#####\s+(.+)$/m',  '<h5>$1</h5>', $s);
    $s = preg_replace('/^####\s+(.+)$/m',   '<h4>$1</h4>', $s);
    $s = preg_replace('/^###\s+(.+)$/m',    '<h3>$1</h3>', $s);
    $s = preg_replace('/^##\s+(.+)$/m',     '<h2>$1</h2>', $s);
    $s = preg_replace('/^#\s+(.+)$/m',      '<h1>$1</h1>', $s);

    // Жирный / курсив. Курсив — только одиночная звёздочка
    // и не внутри жирного (**).
    $s = preg_replace('/\*\*([^*\n]+)\*\*/', '<strong>$1</strong>', $s);
    $s = preg_replace('/(?<!\*)\*([^*\n]+)\*(?!\*)/', '<em>$1</em>', $s);

    // Ссылки [text](url). http(s), относительные (/...), якоря (#...).
    // Внешние — с target="_blank" rel="noopener noreferrer".
    $s = preg_replace_callback(
        '/\[([^\]]+)\]\((https?:\/\/[^)\s]+|[#\/][^)\s]*)\)/',
        static function (array $m): string {
            $href = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
            $text = $m[1];
            $external = (strpos($href, '#') !== 0 && strpos($href, '/') !== 0);
            $attrs = $external ? ' target="_blank" rel="noopener noreferrer"' : '';
            return '<a href="' . $href . '"' . $attrs . '>' . $text . '</a>';
        },
        $s
    );

    // Переносы строк → <br>, чтобы plain-текст читался.
    // Переносы строк → <br>, чтобы plain-текст читался.
    $rvOut = nl2br($s);
    $rvOut = rv_add_heading_ids($rvOut);
    return $baseUrl !== '' ? rv_absolutize_anchors($rvOut, $baseUrl) : $rvOut;
}


/**
 * Санитайзинг HTML-документации через DOMDocument.
 *
 * Белый список тегов:
 *   p, br, hr, h1-h6, ul/ol/li, pre/code, blockquote,
 *   strong/b/em/i/u/s/sub/sup, a, img,
 *   table/thead/tbody/tr/td/th/caption, span, div.
 *
 * Всё остальное (script, style, iframe, object, embed, form, …)
 * удаляется целиком, включая содержимое.
 *
 * Белый список атрибутов:
 *   - на любом теге: class, id;
 *   - на <a>: href, title, rel, target; href — только http(s)/ / /# /mailto;
 *   - на <img>: src, alt, title, width, height; src — только http(s)/ / /#.
 *
 * Всё, что не в белом списке (включая on* обработчики, style,
 * javascript:, data:), отсекается.
 */
function rv_sanitize_html(string $html, string $baseUrl = ''): string
{
    if (trim($html) === '') {
        return '';
    }

    $allowedTags = [
        'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'pre', 'code', 'blockquote',
        'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup',
        'a', 'img', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'caption',
        'span', 'div',
    ];
    $allowedAttrs = [
        '*'   => ['class', 'id'],
        'a'   => ['href', 'title', 'rel', 'target'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
    ];

    // DOMDocument не работает с фрагментами без обёртки — оборачиваем в <div>.
    $doc = new DOMDocument('1.0', 'UTF-8');
    $wrapped = '<?xml encoding="UTF-8"?><div id="__rv_root__">' . $html . '</div>';

    // Глушим libxml-предупреждения на кривой разметке.
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $doc->getElementById('__rv_root__');
    if ($root === null) {
        // DOM не смог разобрать — возвращаем полностью экранированный HTML.
        return htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // Рекурсивный обход. Копируем childNodes в массив,
    // потому что во время обхода дерево мутируется (удаляем узлы).
    $walk = static function (DOMNode $node) use (&$walk, $allowedTags, $allowedAttrs): void {
        if (!$node->hasChildNodes()) {
            return;
        }
        $children = [];
        foreach ($node->childNodes as $c) {
            $children[] = $c;
        }
        foreach ($children as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (!in_array($tag, $allowedTags, true)) {
                    // Тег вне белого списка — удаляем вместе с содержимым.
                    $node->removeChild($child);
                    continue;
                }
                $remove = [];
                foreach ($child->attributes as $attr) {
                    $name = strtolower($attr->name);
                    $allowed = array_merge($allowedAttrs['*'] ?? [], $allowedAttrs[$tag] ?? []);
                    if (!in_array($name, $allowed, true)) {
                        $remove[] = $attr->name;
                        continue;
                    }
                    // Валидация ссылок/картинок: только http(s), относительные,
                    // якорь или mailto.
                    if (in_array($name, ['href', 'src'], true)) {
                        $val = trim($attr->value);
                        if (!preg_match('#^(https?:|/|\#|mailto:)#i', $val)) {
                            $remove[] = $attr->name;
                        }
                    }
                }
                foreach ($remove as $a) {
                    $child->removeAttribute($a);
                }
                $walk($child);
            } elseif (!($child instanceof DOMText)) {
                // Комментарии, CDATA и прочее — вырезаем.
                $node->removeChild($child);
            }
        }
    };
    $walk($root);

    // Сериализуем дочерние узлы обёртки (без самой <div id="__rv_root__">).
    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $doc->saveHTML($c);
    }
    return $baseUrl !== '' ? rv_absolutize_anchors($out, $baseUrl) : $out;
}

/**
 * Заменяет относительные якоря href="#anchor" на абсолютные
 * href="<baseUrl>#anchor". Нужно, потому что Cotonti в шапке темы
 * ставит <base href="...">, и браузер резолвит "#anchor" относительно
 * этого base — уводит на чужой URL без параметров плагина.
 * Затрагивает только атрибуты href, начинающиеся с "#".
 */
function rv_absolutize_anchors(string $html, string $baseUrl): string
{
    if ($baseUrl === '') {
        return $html;
    }
    $baseUrl = rtrim($baseUrl, '#');

    return preg_replace_callback(
        '/\bhref\s*=\s*(["\'])(#[^"\']*)\1/i',
        static function (array $m) use ($baseUrl): string {
            return 'href="' . $baseUrl . $m[2] . '"';
        },
        $html
    );
}

/**
 * Слаг для id заголовка: нижний регистр, пробелы → дефис,
 * удалить всё кроме Unicode-букв, цифр и дефиса.
 */
function rv_slugify(string $text): string
{
    $text = strip_tags($text);
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = preg_replace('/[\s]+/u', '-', $text);
    $text = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

/**
 * Добавляет id к <h1>-<h6>, если его нет. Используется для
 * работы якорей из оглавления (например, <h2>Контакты</h2>
 * получает id="контакты").
 */
function rv_add_heading_ids(string $html): string
{
    if (trim($html) === '' || !preg_match('/<h[1-6][\s>]/i', $html)) {
        return $html;
    }

    $doc = new DOMDocument('1.0', 'UTF-8');
    $wrapped = '<?xml encoding="UTF-8"?><div id="__rv_ids__">' . $html . '</div>';

    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    $root = $doc->getElementById('__rv_ids__');
    if ($root === null) {
        return $html;
    }

    $used = [];
    foreach ($root->getElementsByTagName('*') as $el) {
        $tag = strtolower($el->tagName);
        if (!preg_match('/^h[1-6]$/', $tag)) {
            continue;
        }
        if ($el->hasAttribute('id') && $el->getAttribute('id') !== '') {
            continue;
        }
        $slug = rv_slugify($el->textContent);
        if ($slug === '') {
            continue;
        }
        $base = $slug;
        $n = 2;
        while (isset($used[$slug])) {
            $slug = $base . '-' . $n;
            $n++;
        }
        $used[$slug] = true;
        $el->setAttribute('id', $slug);
    }

    $out = '';
    foreach ($root->childNodes as $c) {
        $out .= $doc->saveHTML($c);
    }
    return $out;
}