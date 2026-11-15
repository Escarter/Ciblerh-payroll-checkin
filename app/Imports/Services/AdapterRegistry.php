<?php

namespace App\Imports\Services;

use App\Imports\Adapters\BaseImportAdapter;
use Illuminate\Support\Facades\File;

class AdapterRegistry
{
    /**
     * Registered adapters: entity_slug => adapter class FQCN
     */
    protected array $adapters = [];

    /**
     * Singleton-cached adapter instances
     */
    protected array $instances = [];

    public function __construct()
    {
        $this->discoverAdapters();
    }

    /**
     * Auto-discover all adapter classes in the Adapters directory.
     */
    protected function discoverAdapters(): void
    {
        $adapterPath = app_path('Imports/Adapters');

        if (!File::isDirectory($adapterPath)) {
            return;
        }

        foreach (File::files($adapterPath) as $file) {
            $className = $file->getFilenameWithoutExtension();

            // Skip the abstract base class
            if ($className === 'BaseImportAdapter') {
                continue;
            }

            $fqcn = "App\\Imports\\Adapters\\{$className}";

            if (!class_exists($fqcn)) {
                continue;
            }

            $reflection = new \ReflectionClass($fqcn);
            if ($reflection->isAbstract() || !$reflection->isSubclassOf(BaseImportAdapter::class)) {
                continue;
            }

            // Instantiate temporarily to get the slug
            $instance = new $fqcn();
            $slug = $instance->getEntitySlug();

            $this->adapters[$slug] = $fqcn;
        }
    }

    /**
     * Register a custom adapter (useful for testing or extensions).
     */
    public function register(string $slug, string $adapterClass): self
    {
        if (!is_subclass_of($adapterClass, BaseImportAdapter::class)) {
            throw new \InvalidArgumentException("{$adapterClass} must extend BaseImportAdapter");
        }
        $this->adapters[$slug] = $adapterClass;
        return $this;
    }

    /**
     * Get an adapter instance by entity slug.
     */
    public function getAdapter(string $slug): ?BaseImportAdapter
    {
        if (!isset($this->adapters[$slug])) {
            return null;
        }

        if (!isset($this->instances[$slug])) {
            $this->instances[$slug] = new $this->adapters[$slug]();
        }

        return $this->instances[$slug];
    }

    /**
     * Get a fresh adapter instance (not cached).
     */
    public function freshAdapter(string $slug): ?BaseImportAdapter
    {
        if (!isset($this->adapters[$slug])) {
            return null;
        }
        return new $this->adapters[$slug]();
    }

    /**
     * Get all available entities as an array of ['slug' => ..., 'name' => ...].
     */
    public function getAvailableEntities(): array
    {
        $entities = [];
        foreach ($this->adapters as $slug => $class) {
            $adapter = $this->getAdapter($slug);
            if ($adapter) {
                $entities[] = [
                    'slug' => $slug,
                    'name' => $adapter->getEntityName(),
                ];
            }
        }
        return $entities;
    }

    /**
     * Check if an entity slug is registered.
     */
    public function hasAdapter(string $slug): bool
    {
        return isset($this->adapters[$slug]);
    }

    /**
     * Get all registered adapter class names.
     */
    public function getRegisteredAdapters(): array
    {
        return $this->adapters;
    }
}
