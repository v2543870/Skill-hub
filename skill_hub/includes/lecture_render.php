<?php
function render_lecture(string $text): string
{
    $inline = function (string $s): string {
        $s = htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $s = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $s);
        $s = preg_replace('/`(.+?)`/u', '<code>$1</code>', $s);
        return $s;
    };
 
    $lines  = explode("\n", str_replace(["\r\n", "\r"], "\n", $text));
    $html   = '';
    $para   = [];
    $inList = false;
    $inCode = false;
    $code   = [];
 
    $flushPara = function () use (&$html, &$para) {
        if ($para) {
            $html .= '<p>' . implode('<br>', $para) . '</p>';
            $para = [];
        }
    };
    $closeList = function () use (&$html, &$inList) {
        if ($inList) {
            $html .= '</ul>';
            $inList = false;
        }
    };
 
    foreach ($lines as $raw) {
        $line = rtrim($raw);
 
        if (strncmp($line, '```', 3) === 0) {
            if ($inCode) {
                $html .= '<pre><code>' . htmlspecialchars(implode("\n", $code), ENT_QUOTES, 'UTF-8') . '</code></pre>';
                $code = [];
                $inCode = false;
            } else {
                $flushPara();
                $closeList();
                $inCode = true;
            }
            continue;
        }
        if ($inCode) {
            $code[] = $raw;
            continue;
        }
 
        if ($line === '') {
            $flushPara();
            $closeList();
            continue;
        }
 
        if (preg_match('/^(#{2,3})\s+(.+)$/u', $line, $m)) {
            $flushPara();
            $closeList();
            $tag = strlen($m[1]) === 2 ? 'h3' : 'h4';
            $html .= "<$tag>" . $inline($m[2]) . "</$tag>";
        } elseif (preg_match('/^-\s+(.+)$/u', $line, $m)) {
            $flushPara();
            if (!$inList) {
                $html .= '<ul>';
                $inList = true;
            }
            $html .= '<li>' . $inline($m[1]) . '</li>';
        } else {
            $closeList();
            $para[] = $inline($line);
        }
    }
 
    if ($inCode) {
        $html .= '<pre><code>' . htmlspecialchars(implode("\n", $code), ENT_QUOTES, 'UTF-8') . '</code></pre>';
    }
    $flushPara();
    $closeList();
 
    return $html;
}