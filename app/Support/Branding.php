<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Branding
{
    public const DEFAULT_PRIMARY = '#1d4ba0';
    public const DEFAULT_SECONDARY = '#3b6fd0';
    public const DEFAULT_SIDEBAR = '#ffffff';
    public const DEFAULT_EMP_NAV = '#ffffff';
    public const DEFAULT_PAGE_BG = '#f7f8fb';
    public const RADII = ['sharp' => ['.4rem', '.3rem'], 'soft' => ['.875rem', '.625rem'], 'round' => ['1.25rem', '.9rem']];
    public const DEFAULT_FONT = 'Poppins';
    public const CACHE_KEY = 'branding.settings';

    public const FONTS = [
        'Poppins' => 'Poppins',
        'Inter' => 'Inter',
        'Roboto' => 'Roboto',
        'Open Sans' => 'Open Sans',
        'Lato' => 'Lato',
        'Nunito' => 'Nunito',
        'Montserrat' => 'Montserrat',
        'Source Sans 3' => 'Source Sans 3',
        'system' => 'System default',
    ];

    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function () {
            $row = null;
            try {
                if (Schema::hasColumn('settings', 'brand_primary_color')) {
                    $row = Setting::first();
                }
            } catch (\Throwable $e) {
                $row = null;
            }

            $primary = self::validColor($row?->brand_primary_color) ?? self::DEFAULT_PRIMARY;
            $font = $row?->brand_font_family;

            return [
                'app_name' => $row?->brand_app_name ?: (config('app.name') ?: 'CibleRH'),
                'logo' => self::fileUrl($row?->brand_logo_path) ?? asset('img/logo.jpg'),
                'favicon' => self::fileUrl($row?->brand_favicon_path) ?? asset('img/fav.jpg'),
                'primary' => $primary,
                'secondary' => self::validColor($row?->brand_secondary_color) ?? self::DEFAULT_SECONDARY,
                'sidebar' => self::validColor($row?->brand_sidebar_color) ?? self::DEFAULT_SIDEBAR,
                'emp_nav' => self::validColor($row?->brand_emp_nav_color) ?? self::DEFAULT_EMP_NAV,
                'emp_hero' => self::validColor($row?->brand_emp_hero_color) ?? $primary,
                'page_bg' => self::validColor($row?->brand_page_bg_color) ?? self::DEFAULT_PAGE_BG,
                'radius' => array_key_exists($row?->brand_radius, self::RADII) ? $row->brand_radius : 'soft',
                'font' => array_key_exists($font, self::FONTS) ? $font : self::DEFAULT_FONT,
                'timezone' => in_array($row?->brand_timezone, timezone_identifiers_list(), true) ? $row->brand_timezone : null,
            ];
        });
    }

    public static function get(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function validColor(?string $color): ?string
    {
        return $color && preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? strtolower($color) : null;
    }

    public static function darken(string $hex, float $amount): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $f = fn ($c) => max(0, min(255, (int) round($c * (1 - $amount))));

        return sprintf('#%02x%02x%02x', $f($r), $f($g), $f($b));
    }

    public static function lighten(string $hex, float $amount): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $f = fn ($c) => max(0, min(255, (int) round($c + (255 - $c) * $amount)));

        return sprintf('#%02x%02x%02x', $f($r), $f($g), $f($b));
    }

    public static function fontUrl(): ?string
    {
        $font = self::get('font');
        if ($font === 'system') {
            return null;
        }

        return 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $font) . ':wght@300;400;500;600;700&display=swap';
    }

    public static function fontStack(): string
    {
        $font = self::get('font');

        return $font === 'system'
            ? "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif"
            : "'{$font}', system-ui, sans-serif";
    }

    public static function css(): string
    {
        $p = self::get('primary');
        $s = self::get('secondary');
        $stack = self::fontStack();

        return ":root{--rf-brand:{$p};--rf-brand-hover:" . self::darken($p, .15)
            . ";--rf-brand-2:" . self::lighten($p, .25)
            . ";--rf-brand-soft:color-mix(in srgb,{$p} 8%,transparent);--rf-brand-ring:color-mix(in srgb,{$p} 18%,transparent);--rf-accent:{$s};}"
            . self::sidebarCss(self::get('sidebar'), $p)
            . self::portalCss()
            . "*{font-family:{$stack};}";
    }

    public static function isDark(string $hex): bool
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) < 150;
    }

    protected static function portalCss(): string
    {
        $css = '';
        [$r, $rs] = self::RADII[self::get('radius')];
        $css .= ":root{--rf-radius:{$r};--rf-radius-sm:{$rs};--rf-bg:" . self::get('page_bg') . ';}';

        $hero = self::get('emp_hero');
        $css .= ".emp-hero{background:radial-gradient(120% 140% at 100% 0%," . self::lighten($hero, .2) . " 0%,transparent 55%),linear-gradient(135deg," . self::darken($hero, .2) . " 0%,{$hero} 60%," . self::lighten($hero, .12) . " 100%)!important;}";

        $nav = self::get('emp_nav');
        if ($nav !== self::DEFAULT_EMP_NAV) {
            $dark = self::isDark($nav);
            $text = $dark ? '#e2e8f0' : '#334155';
            $line = $dark ? self::lighten($nav, .16) : self::darken($nav, .1);
            $active = $dark ? '#ffffff' : self::get('primary');
            $css .= ".emp-portal.emp-topbar,.emp-tabbar{background:{$nav}!important;border-color:{$line}!important;}"
                . ".emp-portal .emp-pill{background:transparent;color:{$text}!important;border-color:{$line};}"
                . ".emp-portal .emp-pill-danger{color:" . ($dark ? '#fecaca' : '#b91c1c') . "!important;}"
                . ".emp-tab{color:" . ($dark ? '#a9b6cc' : '#94a3b8') . "!important;}.emp-tab.active{color:{$active}!important;}"
                . ($dark ? ".emp-portal .emp-brand img{background:#fff;padding:.2rem .45rem;border-radius:.5rem;}" : '');
        }

        return $css;
    }

    protected static function sidebarCss(string $bg, string $primary): string
    {
        if ($bg === self::DEFAULT_SIDEBAR) {
            return '';
        }
        if (self::isDark($bg)) {
            $hover = self::lighten($bg, .1);
            $line = self::lighten($bg, .16);

            return ":root{--rf-sb-bg:{$bg};--rf-sb-text:#e2e8f0;--rf-sb-ink:#fff;--rf-sb-icon:#a9b6cc;--rf-sb-hover:{$hover};--rf-sb-line:{$line};--rf-sb-active-bg:rgba(255,255,255,.14);--rf-sb-active-text:#fff;}"
                . "#sidebarMenu #fullLogo{background:#fff!important;padding:.35rem .6rem;border-radius:.6rem!important;}";
        }
        $hover = self::darken($bg, .05);
        $line = self::darken($bg, .1);

        return ":root{--rf-sb-bg:{$bg};--rf-sb-hover:{$hover};--rf-sb-line:{$line};--rf-sb-active-bg:color-mix(in srgb,{$primary} 12%,transparent);}";
    }

    protected static function fileUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
