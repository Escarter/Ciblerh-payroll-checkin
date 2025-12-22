<?php

namespace App\Livewire;

use Livewire\Component;
use App\Livewire\Traits\WithImportPreview;
use Illuminate\Support\Facades\Gate;

abstract class BaseImportComponent extends Component
{
    use WithImportPreview;

    // Common import properties
    public $autoCreateEntities = false;
    public $sendWelcomeEmails = false; // Whether to send welcome emails to imported employees
    public $selectedCompanyId = null; // For company selection in import modal
    protected $importType = 'generic'; // Override in child classes
    protected $importPermission = null; // Override in child classes

    /**
     * Abstract methods that child classes must implement
     */
    abstract protected function getImportColumns(): array;
    abstract protected function validatePreviewRow(array $rowData, int $rowNumber): array;
    abstract protected function performImport();

    /**
     * Get import context ID for caching
     */
    protected function getImportContextId()
    {
        return $this->importType . '_' . auth()->id() . '_' . now()->timestamp;
    }

    /**
     * Common validation for file uploads
     */
    public function updatedFile()
    {
        $fileProperty = $this->getFileProperty();
        if ($fileProperty) {
            $this->validate([
                $this->getFilePropertyName() => 'sometimes|nullable|mimes:xlsx,xls,csv,txt|max:' . ($this->maxFileSize * 1024)
            ]);

            // Clear previous preview when new file is uploaded
            $this->clearPreview();
        }
    }

    /**
     * Get the file property name (e.g., 'employee_file', 'department_file')
     */
    protected function getFilePropertyName()
    {
        // Extract property name from the file property
        $file = $this->getFileProperty();
        if ($file) {
            // Get the property name that holds the file
            foreach (get_object_vars($this) as $property => $value) {
                if ($value === $file) {
                    return $property;
                }
            }
        }
        return 'file'; // fallback
    }

    /**
     * Import method - dispatches background job with fallback
     */
    public function import()
    {
        // Check permissions
        if ($this->importPermission && !Gate::allows($this->importPermission)) {
            abort(403, __('common.permission_denied'));
        }

        // Validate file exists
        $file = $this->getFileProperty();
        if (!$file) {
            $this->dispatch("showToast", message: __('common.no_file_selected'), type: "danger");
            return;
        }

        // Validate company selection if needed
        if (!$this->getCompanyId()) {
            $this->dispatch("showToast", message: __('companies.select_company_required'), type: "danger");
            return;
        }

        // Validate SMTP settings if sending welcome emails
        if ($this->sendWelcomeEmails ?? false) {
            if (!$this->validateSmtpSettings()) {
                return; // Stop import if SMTP validation fails
            }
        }

        try {
            // Store file temporarily for processing
            $fileName = uniqid('import_') . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('imports', $fileName, 'local');

            // Try to dispatch background job
            try {
                $companyId = $this->getCompanyId();
                $departmentId = $this->getDepartmentId();
                $serviceId = $this->getServiceId();

                \Log::info('BaseImportComponent dispatching ImportDataJob', [
                    'import_type' => $this->importType,
                    'file_path' => $filePath,
                    'user_id' => auth()->id(),
                    'company_id' => $companyId,
                    'department_id' => $departmentId,
                    'service_id' => $serviceId,
                    'auto_create_entities' => $this->autoCreateEntities ?? false
                ]);

                \App\Jobs\ImportDataJob::dispatch(
                    $this->importType,
                    $filePath,
                    auth()->id(),
                    $companyId,
                    $departmentId,
                    $serviceId,
                    $this->autoCreateEntities ?? false,
                    $this->sendWelcomeEmails ?? false
                );

                // Clear form and preview
                $this->clearFields();
                $this->clearPreview();

                // Show success message for job dispatch
                $this->closeModalAndFlashMessage(
                    __('common.import_queued', ['type' => __($this->importType . '.name')]),
                    $this->getModalId()
                );
                return;

            } catch (\Exception $queueException) {
                // Queue failed, log the danger
                \Log::warning("Queue dispatch failed, falling back to sync import", [
                    'danger' => $queueException->getMessage(),
                    'user_id' => auth()->id(),
                    'import_type' => $this->importType
                ]);

                // Fall back to synchronous import for small files
                if ($this->canDoSyncImport($file)) {
                    $this->importSynchronously($filePath);
                    return;
                }

                // For large files, show danger
                $this->dispatch("showToast", message: __('common.import_queue_failed_sync_fallback', [
                    'danger' => $queueException->getMessage()
                ]), type: "danger");
                return;
            }

        } catch (\Exception $e) {
            \Log::danger("Import setup failed for {$this->importType}", [
                'danger' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            $this->dispatch("showToast", message: __('common.import_setup_failed', ['danger' => $e->getMessage()]), type: "danger");
        }
    }

    /**
     * Check if we can do synchronous import (for small files)
     */
    protected function canDoSyncImport($file): bool
    {
        // Only allow sync import for small files (< 100KB) to prevent timeouts
        $maxSyncSize = 100 * 1024; // 100KB
        return $file->getSize() <= $maxSyncSize;
    }

    /**
     * Perform synchronous import as fallback
     */
    protected function importSynchronously($filePath)
    {
        try {
            // Set a reasonable timeout for sync import
            $originalTimeout = ini_get('max_execution_time');
            set_time_limit(120); // 2 minutes max

            // Perform the actual import
            $this->performImport();

            // Restore timeout
            set_time_limit($originalTimeout);

            // Clear form and preview
            $this->clearFields();
            $this->clearPreview();

            // Show success message
            $this->closeModalAndFlashMessage(
                __('common.import_completed', ['type' => __($this->importType . '.name')]),
                $this->getModalId()
            );

        } catch (\Exception $e) {
            // Restore timeout even on danger
            if (isset($originalTimeout)) {
                set_time_limit($originalTimeout);
            }

            \Log::danger("Synchronous import failed for {$this->importType}", [
                'danger' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            $this->dispatch("showToast", message: __('common.import_sync_failed', ['danger' => $e->getMessage()]), type: "danger");
        }
    }

    /**
     * Log the import action
     */
    protected function logImport($result)
    {
        auditLog(
            auth()->user(),
            $this->importType . '_imported',
            'web',
            __($this->importType . '.import_completed', [
                'count' => $result['imported_count'] ?? 0
            ])
        );
    }

    /**
     * Get modal ID for closing
     */
    protected function getModalId()
    {
        return 'import' . ucfirst($this->importType) . 'Modal';
    }

    /**
     * Clear form fields (should be overridden by child classes)
     */
    public function clearFields()
    {
        $this->autoCreateEntities = false;
        $this->sendWelcomeEmails = false;
        // Child classes should override to clear their specific fields
    }

    /**
     * Get company ID for import (should be overridden by child classes)
     */
    protected function getCompanyId(): ?int
    {
        // Use selected company if available, otherwise use component's company
        if ($this->selectedCompanyId) {
            return $this->selectedCompanyId;
        }

        // Fall back to component's company property if it exists
        return property_exists($this, 'company') && $this->company ? $this->company->id : null;
    }

    /**
     * Get department ID for import (should be overridden by child classes)
     */
    protected function getDepartmentId(): ?int
    {
        return null;
    }

    /**
     * Get service ID for import (should be overridden by child classes)
     */
    protected function getServiceId(): ?int
    {
        return null;
    }

    /**
     * Validate SMTP settings are configured when sending welcome emails
     * Returns true if validation passes, false if it fails
     */
    protected function validateSmtpSettings(): bool
    {
        $setting = \App\Models\Setting::first();

        if (!$setting) {
            $this->dispatch("showToast", message: __('common.smtp_not_configured'), type: "danger");
            return false;
        }

        $requiredFields = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_email', 'from_name'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($setting->$field)) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $this->dispatch("showToast", message: __('common.smtp_missing_fields', ['fields' => implode(', ', $missingFields)]), type: "danger");
            return false;
        }

        return true;
    }

    /**
     * Close modal and flash message helper
     */
    protected function closeModalAndFlashMessage($message, $modalId)
    {
        $this->dispatch('close-modal', id: $modalId);
        $this->dispatch("showToast", message: $message, type: "success");
    }
}