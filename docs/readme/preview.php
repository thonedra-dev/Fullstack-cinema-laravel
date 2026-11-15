<?php

// Render the actual README with the project's existing CommonMark dependency.
// Usage: php docs/readme/preview.php [output.html]
$root = dirname(__DIR__, 2);
require $root . '/vendor/autoload.php';
$converter = new League\CommonMark\GithubFlavoredMarkdownConverter([
    'html_input' => 'allow',
    'allow_unsafe_links' => false,
]);
$body = $converter->convert(file_get_contents($root . '/README.md'));
$base = 'file:///' . str_replace('\\', '/', $root) . '/';
$output = $argv[1] ?? sys_get_temp_dir() . '/cinemax-readme-preview.html';
$html = <<<'HTML'
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>CinemaX — README preview</title>
<style>
:root{color-scheme:dark;--bg:#0d1117;--fg:#e6edf3;--border:#30363d;--code:#161b22;--link:#b68aff}
@media(prefers-color-scheme:light){:root{color-scheme:light;--bg:#fff;--fg:#1f2328;--border:#d1d9e0;--code:#f6f8fa;--link:#8250df}}
*{box-sizing:border-box}html{background:var(--bg);color:var(--fg);font:16px/1.5 -apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif}
body{margin:0;padding:24px}.markdown-body{max-width:1012px;margin:auto;padding:32px;border:1px solid var(--border);border-radius:8px;overflow-wrap:break-word}
h1{font-size:27px;font-weight:600;line-height:1.25;margin:0 0 24px;padding-bottom:16px;border-bottom:1px solid var(--border)}
p{margin:0 0 16px}picture{display:block;margin-bottom:20px}img{display:block;max-width:100%;height:auto;box-sizing:content-box}
a{color:var(--link);text-decoration:none}a:hover{text-decoration:underline}details{margin:0 0 16px}summary{cursor:pointer;padding:5px 0}details[open] summary{margin-bottom:16px}
pre{padding:16px;overflow:auto;background:var(--code);border-radius:6px;line-height:1.5;font-size:13px}code{font-family:Consolas,monospace;font-size:85%;background:var(--code);border-radius:4px;padding:.2em .4em}pre code{background:none;padding:0;font-size:100%}
table{display:block;width:max-content;max-width:100%;overflow:auto;border-collapse:collapse;margin:16px 0}th,td{padding:6px 13px;border:1px solid var(--border);text-align:left}tr:nth-child(2n){background:var(--code)}ul{padding-left:2em}li+li{margin-top:.45em}
@media(max-width:600px){body{padding:0}.markdown-body{padding:16px;border:0;border-radius:0}h1{font-size:21px}p[align=center]{font-size:13px}picture{margin-bottom:16px}}
</style><base href="__BASE__"></head><body><article class="markdown-body">__BODY__</article>
<script>if(new URLSearchParams(location.search).has('expanded'))document.querySelectorAll('details').forEach(d=>d.open=true);</script>
</body></html>
HTML;
file_put_contents($output, str_replace(['__BASE__', '__BODY__'], [htmlspecialchars($base, ENT_QUOTES), (string)$body], $html));
echo $output . PHP_EOL;
