<?php

namespace App\Exports\Adapters;

use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Base export adapter. Each entity extends this to provide:
 * - Dynamic column selection
 * - Consistent heading/mapping
 * - Shared query filtering (company, department, search)
 */
abstract class BaseExportAdapter implements FromQuery, WithHeadings, WithMapping, WithStyles
{
    use Exportable;

    protected array $context = [];
    protected array $selectedColumns = [];
    protected string $searchQuery = '';

    // ─── Abstract ──────────────────────────────────────────────────────

    /** Unique slug for this exporter. */
    abstract public function getEntitySlug(): string;

    /** Human-readable entity name. */
    abstract public function getEntityName(): string;

    /**
     * All exportable column definitions.
     *
     * Each item:  ['field' => 'db_column_or_accessor', 'label' => 'Heading', 'default' => true|false]
     */
    abstract public function getColumnDefinitions(): array;

    /** Build the base Eloquent query (before search/filter). */
    abstract protected function baseQuery(): Builder;

    /** Map a single model to a row array in the order of $selectedColumns. */
    abstract protected function mapRow($model): array;

    // ─── Fluent setters ────────────────────────────────────────────────

    public function setContext(array $ctx): static
    {
        $this->context = $ctx;
        return $this;
    }

    public function setSelectedColumns(array $columns): static
    {
        $this->selectedColumns = $columns;
        return $this;
    }

    public function setSearchQuery(string $q): static
    {
        $this->searchQuery = $q;
        return $this;
    }

    // ─── Defaults ──────────────────────────────────────────────────────

    /**
     * Get the default columns (those marked 'default' => true).
     */
    public function getDefaultColumns(): array
    {
        return collect($this->getColumnDefinitions())
            ->filter(fn($c) => $c['default'] ?? true)
            ->pluck('field')
            ->toArray();
    }

    /**
     * Determine effective column list. If none selected, use defaults.
     */
    protected function effectiveColumns(): array
    {
        return !empty($this->selectedColumns)
            ? $this->selectedColumns
            : $this->getDefaultColumns();
    }

    // ─── Interface implementations ─────────────────────────────────────

    public function headings(): array
    {
        $defs = collect($this->getColumnDefinitions())->keyBy('field');
        return collect($this->effectiveColumns())
            ->map(fn($col) => $defs[$col]['label'] ?? $col)
            ->toArray();
    }

    public function query()
    {
        return $this->baseQuery();
    }

    public function map($model): array
    {
        return $this->mapRow($model);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    // ─── Template filename ─────────────────────────────────────────────

    public function getFilename(string $format = 'xlsx'): string
    {
        $slug = $this->getEntitySlug();
        $date = now()->format('Y-m-d_His');
        return "export_{$slug}_{$date}.{$format}";
    }
}
