<?php

namespace App\Livewire\Traits;

/**
 * Shared row selection for list pages.
 *
 * A component supplies:
 *  - bulkQuery(): the table query for the current tab (scoped, ordered, not paginated)
 *  - bulkSelectionMap(): ['active' => [selectedProp, flagProp], 'deleted' => [selectedProp, flagProp]]
 *    (omit 'deleted' when both tabs share one selection)
 *
 * Header checkboxes should use wire:model.live on the flag property; the updated hooks below do the rest.
 */
trait WithBulkSelection
{
    // When true the header checkbox selects every matching row instead of the current page.
    protected function bulkAcrossPages(): bool
    {
        return false;
    }

    protected function bulkPageSize(): int
    {
        return (int) $this->perPage;
    }

    abstract protected function bulkQuery();

    abstract protected function bulkSelectionMap(): array;

    protected function bulkTab(): string
    {
        return property_exists($this, 'activeTab') && $this->activeTab === 'deleted' ? 'deleted' : 'active';
    }

    protected function bulkTarget(?string $tab = null): array
    {
        $map = $this->bulkSelectionMap();

        return $map[$tab ?? $this->bulkTab()] ?? $map['active'];
    }

    protected function bulkIds(bool $allPages): array
    {
        $query = $this->bulkQuery();

        if (! $allPages) {
            $query->forPage(method_exists($this, 'getPage') ? $this->getPage() : 1, $this->bulkPageSize());
        }

        return $query->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    protected function bulkSelected(string $property): array
    {
        return array_map('strval', (array) $this->{$property});
    }

    protected function applyBulkSelection(string $tab, bool $checked, ?bool $allPages = null): void
    {
        [$property, $flag] = $this->bulkTarget($tab);
        $allPages ??= $this->bulkAcrossPages();

        $ids = $this->bulkIds($allPages);
        $current = $this->bulkSelected($property);

        $this->{$property} = $checked
            ? array_values(array_unique(array_merge($current, $ids)))
            : ($allPages ? [] : array_values(array_diff($current, $ids)));

        $this->{$flag} = $checked;
        $this->bulkSelectionChanged($tab);
    }

    protected function refreshBulkFlag(string $tab): void
    {
        [$property, $flag] = $this->bulkTarget($tab);
        $ids = $this->bulkIds(false);

        $this->{$flag} = $ids !== [] && array_diff($ids, $this->bulkSelected($property)) === [];
    }

    // Override to react to selection changes (e.g. enable bulk action buttons).
    protected function bulkSelectionChanged(string $tab): void
    {
    }

    public function updatedSelectAll($value): void
    {
        $this->applyBulkSelection('active', (bool) $value);
    }

    public function updatedSelectAllForDelete($value): void
    {
        $this->applyBulkSelection('deleted', (bool) $value);
    }

    public function toggleSelectAll(): void
    {
        [, $flag] = $this->bulkTarget();
        $this->applyBulkSelection($this->bulkTab(), ! $this->{$flag});
    }

    public function toggleSelection($id): void
    {
        [$property] = $this->bulkTarget();
        $id = (string) $id;
        $current = $this->bulkSelected($property);

        $this->{$property} = in_array($id, $current, true)
            ? array_values(array_diff($current, [$id]))
            : array_values(array_merge($current, [$id]));

        $this->refreshBulkFlag($this->bulkTab());
        $this->bulkSelectionChanged($this->bulkTab());
    }

    public function selectAllVisible(): void
    {
        $this->applyBulkSelection($this->bulkTab(), true, false);
    }

    public function selectAllMatching(): void
    {
        $this->applyBulkSelection($this->bulkTab(), true, true);
    }

    public function clearSelection(): void
    {
        [$property, $flag] = $this->bulkTarget();
        $this->{$property} = [];
        $this->{$flag} = false;
        $this->bulkSelectionChanged($this->bulkTab());
    }

    protected function resetBulkSelection(): void
    {
        foreach ($this->bulkSelectionMap() as [$property, $flag]) {
            $this->{$property} = [];
            $this->{$flag} = false;
        }
    }
}
