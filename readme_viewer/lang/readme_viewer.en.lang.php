<?php
/**
 * File: plugins/readme_viewer/lang/readme_viewer.en.lang.php
 *
 * English localization of the readme_viewer plugin.
 * The core picks it up automatically via cot_langfile('readme_viewer', 'plug')
 * when Cot::$usr['lang'] === 'en'.
 *
 * ─ info_* ──────────────────────────────────────────────────────────────
 *   Used in admin when displaying the extension card
 *   (see cot_get_extensionparams() in system/admin/admin.functions.php).
 *
 * ─ cfg_* ──────────────────────────────────────────────────────────────
 *   Headers and parameters of config fields from the setup file. Cotonti will
 *   substitute them into the plugin settings form.
 *
 * ─ rv_* ───────────────────────────────────────────────────────────────
 *   Strings used by the frontend (button in admin, view page).
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
$L['info_desc']  = 'Allows standardizing documentation for extensions and provides the super-administrator with safe viewing of extension README files via a link from the admin panel in the frontend.';
$L['info_notes'] = 'Searches for README.md / README.html / README.txt in the extension folder (_dir_readme_viewer), then in the plugin library (_dir_readme_viewer_list). Adds a “Documentation” button to the extension page in admin.';

$L['readme_viewer_title'] = $L['info_name'];
$L['readme_viewer_desc']  = $L['info_desc'];
$L['readme_viewer_name']  = $L['info_name'];


$L['rv_open_doc']          = 'Open extension documentation';
$L['rv_open_doc_short']    = 'Documentation';
$L['rv_not_found']         = 'Documentation file not found.';
$L['rv_insecure_warning']  = 'Warning: this page is opened over an insecure HTTP connection.';
$L['rv_error_too_large']   = 'The documentation file is too large to display.';
$L['rv_error_read_failed'] = 'Failed to read the documentation file.';

$L['cfg_rv_enforce_https']        = 'Disallow viewing over HTTP';
$L['cfg_rv_enforce_https_hint']   = ''
    . '0 (NO) — soft mode: on HTTP show a warning, but still read the file <br>'
    . '(required for local development) <br>'
    . '<hr>'
    . '1 (YES) — hard mode: on HTTP do not read the file at all, show only the warning.'
    . '';
	
	
$L['cfg_rv_html_mode']            = 'HTML documentation display mode';
$L['cfg_rv_html_mode_params']     = ['sanitize' => 'Sanitizing (allowlist)', 'plain' => 'As source code'];


$L['cfg_rv_max_size']        = 'Maximum file size, bytes';
$L['cfg_rv_max_size_hint']   = ''
    . 'File size limit in bytes. 1048576 = 1 MB. <br>'
    . 'Protection against attempts to read and render a multi-megabyte README and crash PHP. <br>'
    . '1048576 bytes (approximately 1 MB) <br>'
    . '2097152 bytes (approximately 2 MB) <br>'
    . '3145728 bytes (approximately 3 MB) <br>'
    . '5242880 bytes (approximately 5 MB)'
    . '';
	
/* does not work with numeric values
	$L['cfg_rv_max_size_params'] = [
    '1048576' => '1048576 bytes (approximately 1 MB)',
    '2097152' => '2097152 bytes (approximately 2 MB)',
    '3145728' => '3145728 bytes (approximately 3 MB)',
    '5242880' => '5242880 bytes (approximately 5 MB)'
]; 
 */