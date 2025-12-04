<?php

namespace App\Utils;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class PermissionHelper
{
    /**
     * Check if user has any of the given permissions
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        
        foreach ($permissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public static function hasAllPermissions(array $permissions): bool
    {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        
        foreach ($permissions as $permission) {
            if (!$user->hasPermissionTo($permission)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Build the list of global search links the current user can access.
     */
    public static function accessibleGlobalSearchLinks(): array
    {
        if (!Auth::check()) {
            return [];
        }

        $config = config('app.global_search', []);
        $items = $config['items'] ?? [];
        $bypass = (bool) ($config['bypass_permissions'] ?? false);
        $user = Auth::user();

        return collect($items)
            ->filter(fn(array $item) => self::canAccessGlobalSearchItem($item, $user, $bypass))
            ->map(fn(array $item) => self::mapGlobalSearchItem($item))
            ->values()
            ->all();
    }

    private static function canAccessGlobalSearchItem(array $item, Authenticatable $user, bool $bypass): bool
    {
        if ($bypass) {
            return true;
        }

        // Check for admin roles
        if (method_exists($user, 'hasAnyRole')) {
            $privilegedRoles = ['admin', 'super-admin', 'super admin', 'administrator'];
            if ($user->hasAnyRole($privilegedRoles)) {
                return true;
            }
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return true;
        }

        $permissions = Arr::wrap($item['permissions'] ?? []);
        $permissionType = $item['permission_type'] ?? 'all';

        if (!empty($permissions) && !self::userHasGlobalSearchPermissions($user, $permissions, $permissionType)) {
            return false;
        }

        $roles = Arr::wrap($item['roles'] ?? []);
        if (!empty($roles) && method_exists($user, 'hasAnyRole') && !$user->hasAnyRole($roles)) {
            return false;
        }

        return true;
    }

    private static function userHasGlobalSearchPermissions(Authenticatable $user, array $permissions, string $type): bool
    {
        if ($type === 'any') {
            return collect($permissions)->some(fn(string $permission) => self::userHasPermission($user, $permission));
        }

        return collect($permissions)->every(fn(string $permission) => self::userHasPermission($user, $permission));
    }

    private static function userHasPermission(Authenticatable $user, string $permission): bool
    {
        if (method_exists($user, 'can') && $user->can($permission)) {
            return true;
        }

        if (method_exists($user, 'hasPermissionTo') && $user->hasPermissionTo($permission)) {
            return true;
        }

        if (method_exists($user, 'hasAnyPermission') && $user->hasAnyPermission([$permission])) {
            return true;
        }

        return false;
    }

    private static function mapGlobalSearchItem(array $item): array
    {
        $label = self::translateSearchValue($item['label'] ?? '');
        $group = self::translateSearchValue($item['group'] ?? '');
        $description = self::translateSearchValue($item['description'] ?? '');

        $keywords = collect($item['keywords'] ?? [])
            ->flatMap(fn(string $keyword) => [$keyword, self::translateSearchValue($keyword)])
            ->filter()
            ->unique()
            ->values()
            ->all();

        $route = $item['route'] ?? null;
        $url = $item['url'] ?? null;

        if (!$url && $route && Route::has($route)) {
            $url = route($route, $item['route_params'] ?? []);
        }

        $url = $url ?: '#';

        $searchTerms = array_values(array_filter(array_unique(array_merge(
            [$label, $group, $description],
            array_filter([$item['label'] ?? null, $item['group'] ?? null, $item['description'] ?? null]),
            $keywords,
            [$route ?? '', $url]
        ))));

        return [
            'id' => $item['id'] ?? md5($label . $url),
            'label' => $label,
            'group' => $group,
            'description' => $description,
            'url' => $url,
            'icon' => $item['icon'] ?? null,
            'search_terms' => $searchTerms,
        ];
    }

    private static function translateSearchValue(?string $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Str::contains($value, '.') ? __($value) : $value;
    }
}