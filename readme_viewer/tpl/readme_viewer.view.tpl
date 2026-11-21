<!-- File: plugins/readme_viewer/tpl/readme_viewer.view.tpl -->
<!--
    Шаблон страницы просмотра README-файла.
    Используется из inc/readme_viewer.view.php.
	
    Теги:
	RV_INSECURE_WARNING — текст предупреждения HTTP-режима;
	RV_TITLE            — код расширения (например, page);
	RV_CONTENT          — уже безопасно отрендеренный HTML-документ;
	RV_ERROR_TEXT       — текст ошибки чтения/размера.
	
	readme_viewer plugin for Cotonti v1.+, PHP 8.5+, MySQL 8.4
	
	ReadMeMore:       https://abuyfile.com/ru/market/cotonti/plugs/readme_viewer
	Support:          https://abuyfile.com/forums/cotonti/custom/plugs/
	Source:           https://github.com/webitproff/readme_viewer-cotonti
	
	Date: 2026-11-21
	package readme_viewer
	version 1.3.3
	author webitproff
	copyright Copyright (c) webitproff 2026 | https://github.com/webitproff/readme_viewer-cotonti
	license BSD
	
	
-->
<!-- BEGIN: MAIN -->

<style>
	.rv-doc { max-width: 100%; }
	.rv-body { line-height: 1.55; word-wrap: break-word; }
	/* Инлайн-код внутри текста (абзацы, списки, заголовки) */
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
	
	/* Блок кода pre — тёмный контейнер со скроллом */
	.rv-body pre,
	.rv-pre {
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
	
	/* code внутри pre — без собственного фона и рамки */
	.rv-body pre > code,
	.rv-pre > code {
	background: transparent;
	color: inherit;
	font-size: inherit;
	line-height: inherit;
	padding: 0;
	border: 0;
	border-radius: 0;
	white-space: pre;
	}
	
	/* Таблицы с горизонтальным скроллом на мобильных */
	.rv-body table {
	display: block;
	width: 100%;
	max-width: 100%;
	overflow-x: auto;
	-webkit-overflow-scrolling: touch;
	border-collapse: collapse;
	}
	.rv-body th, .rv-body td { border: 1px solid #d0d7de; padding: 4px 8px; }
	/* Инлайн-код в ячейках таблиц — не переносить и не сжимать */
	.rv-body th code,
	.rv-body td code {
	white-space: nowrap;
	word-break: normal;
	overflow-wrap: normal;
	display: inline-block;
	min-width: max-content;
	max-width: none;
	}
	.rv-insecure-warning {
	background: #fff3cd; border: 1px solid #ffe69c; color: #664d03;
	padding: 8px 12px; border-radius: 4px; margin-bottom: 12px;
	}
	.rv-not-found { color: #b32d2e; }
</style>
<div class="rv-doc">
	<!-- BEGIN: RV_INSECURE -->
	<div class="rv-insecure-warning">{RV_INSECURE_WARNING}</div>
	<!-- END: RV_INSECURE -->
	
	<!-- BEGIN: RV_OK -->
	<div class="alert alert-info" role="alert">
		<h2>{RV_TITLE}</h2>
		<p class="rv-source">
			<pre>{RV_FILE_PATH}</pre>
		</p>
		<p class="rv-source">
			<span class="rv-file-ext">[{RV_FILE_NAME}][{RV_FILE_EXT}]</span>
		</p>
	</div>
	
	<div class="rv-body card card-body">{RV_CONTENT}</div>
	<!-- END: RV_OK -->
	
	<!-- BEGIN: RV_ERROR -->
	<p class="rv-not-found">{RV_ERROR_TEXT}</p>
	<!-- END: RV_ERROR -->
	
	<!-- BEGIN: RV_NOT_FOUND -->
	<p class="rv-not-found">{PHP.L.rv_not_found}</p>
	<!-- END: RV_NOT_FOUND -->
</div>

<!-- IF {PHP|cot_plugin_active('cotcp')} AND {PHP.cfg.plugin.cotcp.tplpathenableview} AND {PHP.usr.maingrp} == 5 -->
<!-- IF {PHP|function_exists('cot_debug_tpl_url')} -->
<div class="alert alert-secondary mt-5" role="alert">
	<div class="fw-semibold mb-2 small">{PHP.L.cotcp_path_current_tpl}</div>
	<div class="text-danger fw-semibold">{PHP|cot_debug_tpl_url()}</div>
</div>
<!-- ENDIF -->
<!-- ENDIF -->
<!-- END: MAIN -->

