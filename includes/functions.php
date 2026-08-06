<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
function e(?string $value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function url(string $path=''): string { $base = trim((string) SITE_URL) !== '' ? rtrim((string) SITE_URL, '/') : rtrim(detected_site_url(), '/'); return $base . ($path !== '' ? '/' . ltrim($path, '/') : '/'); }
function current_page(): string { return basename($_SERVER['PHP_SELF'] ?? 'index.php'); }
function active(string ...$pages): string { return in_array(current_page(), $pages, true) ? ' active' : ''; }
function icon(string $name): string {
  $paths = [
    'check'=>'<path d="M20 6 9 17l-5-5"/>','calendar'=>'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/>',
    'camera'=>'<path d="M14.5 4 16 7h3a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h3l1.5-3h5Z"/><circle cx="12" cy="13" r="3"/>',
    'file'=>'<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M8 13h8M8 17h6"/>',
    'shield'=>'<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/>',
    'home'=>'<path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10M9 20v-6h6v6"/>','layers'=>'<path d="m12 2 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/>',
    'ruler'=>'<path d="M21.3 8.7 8.7 21.3a2.4 2.4 0 0 1-3.4 0l-2.6-2.6a2.4 2.4 0 0 1 0-3.4L15.3 2.7a2.4 2.4 0 0 1 3.4 0l2.6 2.6a2.4 2.4 0 0 1 0 3.4Z"/><path d="m7.5 10.5 2 2m1-5 2 2m1-5 2 2"/>',
    'hammer'=>'<path d="m15 5 4 4M14 6l-3-3-3 3 3 3M13 8 5 20l-3-3 12-8"/>','sparkles'=>'<path d="m12 3-1.2 3.2L8 8l2.8 1.8L12 13l1.2-3.2L16 8l-2.8-1.8L12 3ZM5 14l-.8 2.2L2 17.5l2.2 1.3L5 21l.8-2.2 2.2-1.3-2.2-1.3L5 14Zm14-2-.8 2.2-2.2 1.3 2.2 1.3L19 19l.8-2.2 2.2-1.3-2.2-1.3L19 12Z"/>',
    'arrow'=>'<path d="M5 12h14M13 6l6 6-6 6"/>','mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>','map-pin'=>'<path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>','clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>','menu'=>'<path d="M4 6h16M4 12h16M4 18h16"/>','x'=>'<path d="m6 6 12 12M18 6 6 18"/>','chevron-down'=>'<path d="m6 9 6 6 6-6"/>','book'=>'<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/>','paint'=>'<path d="M12 22a10 10 0 1 0 0-20 10 10 0 0 0 0 20Z"/><circle cx="7.5" cy="10.5" r="1"/><circle cx="10" cy="6.5" r="1"/><circle cx="14.5" cy="6.5" r="1"/><circle cx="17" cy="10.5" r="1"/><path d="M12 22c2 0 2-3 0-3h-1a2 2 0 0 1 0-4h3"/>','phone'=>'<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.33 1.78.62 2.63a2 2 0 0 1-.45 2.11L8 9.73a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.85.29 1.73.5 2.63.62A2 2 0 0 1 22 16.92Z"/>'
  ];
  return '<svg class="icon" viewBox="0 0 24 24" aria-hidden="true">'.($paths[$name]??$paths['check']).'</svg>';
}
function service_data(string $key): array { $all=require __DIR__.'/../data/services.php'; return $all[$key] ?? []; }
