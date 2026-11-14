<?php

namespace App\Livewire\Portal\Settings;

use App\Models\Setting;
use App\Support\Branding;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Appearance extends Component
{
    use WithFileUploads;

    public $app_name = '';
    public $primary_color = Branding::DEFAULT_PRIMARY;
    public $secondary_color = Branding::DEFAULT_SECONDARY;
    public $sidebar_color = Branding::DEFAULT_SIDEBAR;
    public $emp_nav_color = Branding::DEFAULT_EMP_NAV;
    public $emp_hero_color = Branding::DEFAULT_PRIMARY;
    public $page_bg_color = Branding::DEFAULT_PAGE_BG;
    public $radius = 'soft';
    public $font_family = Branding::DEFAULT_FONT;
    public $timezone = '';
    public $show_logo = true;
    public $logo;
    public $favicon;
    public $auth_bg;
    public $current_logo;
    public $current_favicon;
    public $current_auth_bg;
    public $auth_kicker = '';
    public $auth_title = '';
    public $auth_subtitle = '';
    public $auth_feature_1 = '';
    public $auth_feature_2 = '';
    public $auth_feature_3 = '';
    public $auth_footer = '';

    public function mount()
    {
        abort_unless(Gate::allows('setting-read'), 403);
        $this->fillFromSettings();
    }

    protected function fillFromSettings(): void
    {
        $b = Branding::all();
        $setting = Setting::first();
        $this->app_name = $setting?->brand_app_name ?? '';
        $this->primary_color = $b['primary'];
        $this->secondary_color = $b['secondary'];
        $this->sidebar_color = $b['sidebar'];
        $this->emp_nav_color = $b['emp_nav'];
        $this->emp_hero_color = $b['emp_hero'];
        $this->page_bg_color = $b['page_bg'];
        $this->show_logo = $b['show_logo'] ?? true;
        $this->radius = $b['radius'];
        $this->font_family = $b['font'];
        $this->timezone = $b['timezone'] ?? config('app.timezone');
        $this->current_logo = $b['logo'];
        $this->current_favicon = $b['favicon'];
        $this->current_auth_bg = $b['auth_bg'] ?? null;
        $this->auth_kicker = $setting?->brand_auth_kicker ?? '';
        $this->auth_title = $setting?->brand_auth_title ?? '';
        $this->auth_subtitle = $setting?->brand_auth_subtitle ?? '';
        $this->auth_feature_1 = $setting?->brand_auth_feature_1 ?? '';
        $this->auth_feature_2 = $setting?->brand_auth_feature_2 ?? '';
        $this->auth_feature_3 = $setting?->brand_auth_feature_3 ?? '';
        $this->auth_footer = $setting?->brand_auth_footer ?? '';
    }

    protected function rules(): array
    {
        return [
            'app_name' => ['nullable', 'string', 'max:80'],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'secondary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'sidebar_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'emp_nav_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'emp_hero_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'page_bg_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'show_logo' => ['boolean'],
            'radius' => ['required', 'in:sharp,soft,round'],
            'font_family' => ['required', 'in:' . implode(',', array_keys(Branding::FONTS))],
            'timezone' => ['required', 'in:' . implode(',', timezone_identifiers_list())],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:2048'],
            'favicon' => ['nullable', 'image', 'mimes:png,jpg,jpeg,ico,svg,webp', 'max:1024'],
            'auth_bg' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg,webp', 'max:4096'],
            'auth_kicker' => ['nullable', 'string', 'max:120'],
            'auth_title' => ['nullable', 'string', 'max:200'],
            'auth_subtitle' => ['nullable', 'string', 'max:500'],
            'auth_feature_1' => ['nullable', 'string', 'max:200'],
            'auth_feature_2' => ['nullable', 'string', 'max:200'],
            'auth_feature_3' => ['nullable', 'string', 'max:200'],
            'auth_footer' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function save()
    {
        abort_unless(Gate::allows('setting-save'), 403);
        $this->validate();

        $setting = Setting::first() ?? new Setting();
        $data = [
            'brand_app_name' => $this->app_name ?: null,
            'brand_primary_color' => strtolower($this->primary_color),
            'brand_secondary_color' => strtolower($this->secondary_color),
            'brand_sidebar_color' => strtolower($this->sidebar_color),
            'brand_emp_nav_color' => strtolower($this->emp_nav_color),
            'brand_emp_hero_color' => strtolower($this->emp_hero_color),
            'brand_page_bg_color' => strtolower($this->page_bg_color),
            'brand_show_logo' => (bool) $this->show_logo,
            'brand_radius' => $this->radius,
            'brand_font_family' => $this->font_family,
            'brand_timezone' => $this->timezone,
            'brand_auth_kicker' => $this->auth_kicker ?: null,
            'brand_auth_title' => $this->auth_title ?: null,
            'brand_auth_subtitle' => $this->auth_subtitle ?: null,
            'brand_auth_feature_1' => $this->auth_feature_1 ?: null,
            'brand_auth_feature_2' => $this->auth_feature_2 ?: null,
            'brand_auth_feature_3' => $this->auth_feature_3 ?: null,
            'brand_auth_footer' => $this->auth_footer ?: null,
        ];

        if ($this->logo) {
            $this->deleteOld($setting->brand_logo_path);
            $data['brand_logo_path'] = $this->logo->store('branding', 'public');
        }
        if ($this->favicon) {
            $this->deleteOld($setting->brand_favicon_path);
            $data['brand_favicon_path'] = $this->favicon->store('branding', 'public');
        }
        if ($this->auth_bg) {
            $this->deleteOld($setting->brand_auth_bg_path);
            $data['brand_auth_bg_path'] = $this->auth_bg->store('branding', 'public');
        }

        $setting->fill($data)->save();
        Branding::flush();

        $this->reset(['logo', 'favicon', 'auth_bg']);
        $this->fillFromSettings();
        session()->flash('message', __('settings.appearance_saved'));

        return $this->redirect(route('portal.settings.appearance'), navigate: false);
    }

    public function removeAsset(string $type)
    {
        abort_unless(Gate::allows('setting-save'), 403);
        if (!in_array($type, ['logo', 'favicon', 'auth_bg'], true)) {
            return;
        }
        $setting = Setting::first();
        $col = "brand_{$type}_path";
        if ($setting && $setting->$col) {
            $this->deleteOld($setting->$col);
            $setting->update([$col => null]);
            Branding::flush();
        }
        $this->fillFromSettings();
    }

    public function resetDefaults()
    {
        abort_unless(Gate::allows('setting-save'), 403);
        $setting = Setting::first();
        if ($setting) {
            $this->deleteOld($setting->brand_logo_path);
            $this->deleteOld($setting->brand_favicon_path);
            $this->deleteOld($setting->brand_auth_bg_path);
            $setting->update([
                'brand_app_name' => null, 'brand_logo_path' => null, 'brand_favicon_path' => null, 'brand_auth_bg_path' => null,
                'brand_primary_color' => null, 'brand_secondary_color' => null, 'brand_sidebar_color' => null, 'brand_emp_nav_color' => null, 'brand_emp_hero_color' => null, 'brand_page_bg_color' => null, 'brand_radius' => null, 'brand_show_logo' => true,
                'brand_font_family' => null, 'brand_timezone' => null,
                'brand_auth_kicker' => null, 'brand_auth_title' => null, 'brand_auth_subtitle' => null,
                'brand_auth_feature_1' => null, 'brand_auth_feature_2' => null, 'brand_auth_feature_3' => null, 'brand_auth_footer' => null,
            ]);
        }
        Branding::flush();
        session()->flash('message', __('settings.appearance_reset'));

        return $this->redirect(route('portal.settings.appearance'), navigate: false);
    }

    protected function deleteOld(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    public function render()
    {
        return view('livewire.portal.settings.appearance', [
            'fonts' => Branding::FONTS,
            'timezones' => timezone_identifiers_list(),
        ])->layout('components.layouts.dashboard');
    }
}
