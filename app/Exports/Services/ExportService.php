<?php

namespace App\Exports\Services;

use App\Exports\Adapters\BaseExportAdapter;
use App\Exports\Adapters\AbsenceExportAdapter;
use App\Exports\Adapters\AdvanceSalaryExportAdapter;
use App\Exports\Adapters\AuditLogExportAdapter;
use App\Exports\Adapters\CompanyExportAdapter;
use App\Exports\Adapters\DepartmentExportAdapter;
use App\Exports\Adapters\EmployeeExportAdapter;
use App\Exports\Adapters\LeaveExportAdapter;
use App\Exports\Adapters\LeaveTypeExportAdapter;
use App\Exports\Adapters\OvertimeExportAdapter;
use App\Exports\Adapters\PayslipExportAdapter;
use App\Exports\Adapters\ServiceExportAdapter;
use App\Exports\Adapters\TickingExportAdapter;

class ExportService
{
    /**
     * Registered export adapter classes keyed by slug.
     */
    protected array $adapters = [
        'employees'        => EmployeeExportAdapter::class,
        'companies'        => CompanyExportAdapter::class,
        'departments'      => DepartmentExportAdapter::class,
        'services'         => ServiceExportAdapter::class,
        'leave_types'      => LeaveTypeExportAdapter::class,
        'leaves'           => LeaveExportAdapter::class,
        'absences'         => AbsenceExportAdapter::class,
        'overtimes'        => OvertimeExportAdapter::class,
        'payslips'         => PayslipExportAdapter::class,
        'tickings'         => TickingExportAdapter::class,
        'advance_salaries' => AdvanceSalaryExportAdapter::class,
        'audit_logs'       => AuditLogExportAdapter::class,
    ];

    /**
     * Get all available export entities.
     */
    public function getAvailableEntities(): array
    {
        $entities = [];
        foreach ($this->adapters as $slug => $class) {
            $adapter = new $class();
            $entities[] = [
                'slug' => $slug,
                'name' => $adapter->getEntityName(),
                'columns' => $adapter->getColumnDefinitions(),
                'default_columns' => $adapter->getDefaultColumns(),
            ];
        }
        return $entities;
    }

    /**
     * Build a fresh adapter instance for the given slug.
     */
    public function getAdapter(string $slug): ?BaseExportAdapter
    {
        $class = $this->adapters[$slug] ?? null;
        return $class ? new $class() : null;
    }

    /**
     * Get an adapter configured for export.
     */
    public function prepareExport(
        string $slug,
        array $context = [],
        array $selectedColumns = [],
        string $search = ''
    ): BaseExportAdapter {
        $adapter = $this->getAdapter($slug);

        if (!$adapter) {
            throw new \InvalidArgumentException("Unknown export entity: {$slug}");
        }

        return $adapter
            ->setContext($context)
            ->setSelectedColumns($selectedColumns)
            ->setSearchQuery($search);
    }

    /**
     * Get the column definitions for a given entity.
     */
    public function getColumns(string $slug): array
    {
        $adapter = $this->getAdapter($slug);
        return $adapter ? $adapter->getColumnDefinitions() : [];
    }
}
