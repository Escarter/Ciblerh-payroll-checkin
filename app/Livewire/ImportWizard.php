<?php

namespace App\Livewire;

use App\Imports\Services\AdapterRegistry;
use App\Imports\Services\FieldMappingService;
use App\Imports\Services\ImportService;
use App\Jobs\ProcessAdapterImportJob;
use App\Models\ImportJob;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class ImportWizard extends Component
{
    use WithFileUploads;

    // ─── Step management ───────────────────────────────────────────────
    public int $currentStep = 1; // 1=upload, 2=map, 3=preview, 4=import

    // ─── Step 1: Upload & Configure ────────────────────────────────────
    public $file;
    public string $entitySlug = '';
    public string $importMode = 'create_only';
    public bool $autoCreateEntities = false;
    public bool $sendWelcomeEmails = false;
    public ?int $selectedCompanyId = null;
    public ?int $selectedDepartmentId = null;
    public ?int $selectedServiceId = null;

    // ─── Step 2: Field Mapping ─────────────────────────────────────────
    public array $csvHeaders = [];
    public array $fieldMapping = []; // csv_index => field_name
    public array $autoMappingSuggestions = [];
    public array $availableFields = [];
    public array $unmappedRequired = [];
    public array $rawRows = [];

    // ─── Step 3: Preview ───────────────────────────────────────────────
    public array $previewData = [];
    public int $previewValidCount = 0;
    public int $previewErrorCount = 0;
    public array $previewErrors = [];

    // ─── Step 4: Import ────────────────────────────────────────────────
    public bool $isImporting = false;
    public ?int $importJobId = null;
    public array $importResult = [];
    public int $processedRows = 0;
    public int $totalRows = 0;

    // Stored immediately after upload so executeImport() uses the exact path
    public ?string $storedFilePath = null;

    // ─── Available options (populated on mount) ────────────────────────
    public array $availableEntities = [];
    public array $companies = [];
    public array $departments = [];
    public array $services = [];

    protected $listeners = ['refreshImportStatus'];

    public function mount()
    {
        $registry = app(AdapterRegistry::class);
        $this->availableEntities = $registry->getAvailableEntities();

        // Load companies for context selection
        $this->companies = \App\Models\Company::orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    // ─── Step 1 Methods ────────────────────────────────────────────────

    public function updatedSelectedCompanyId($value)
    {
        $this->departments = [];
        $this->services = [];
        $this->selectedDepartmentId = null;
        $this->selectedServiceId = null;

        if ($value) {
            $this->departments = \App\Models\Department::where('company_id', $value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        }
    }

    public function updatedSelectedDepartmentId($value)
    {
        $this->services = [];
        $this->selectedServiceId = null;

        if ($value) {
            $this->services = \App\Models\Service::where('department_id', $value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        }
    }

    public function updatedEntitySlug()
    {
        // Reset mapping when entity changes
        $this->csvHeaders = [];
        $this->fieldMapping = [];
        $this->autoMappingSuggestions = [];
        $this->rawRows = [];
        $this->previewData = [];
    }

    public function proceedToMapping()
    {
        $this->validate([
            'file' => 'required|mimes:xlsx,xls,csv,txt|max:51200',
            'entitySlug' => 'required|string',
        ]);

        if (!$this->entitySlug) {
            $this->dispatch('showToast', message: __('import.select_entity_type'), type: 'danger');
            return;
        }

        try {
            // Store file and capture the exact path for later use in executeImport()
            $fileName = uniqid('import_') . '_' . $this->file->getClientOriginalName();
            $filePath = $this->file->storeAs('imports', $fileName, 'local');
            $this->storedFilePath = $filePath;

            // Parse file
            $importService = app(ImportService::class);
            $parsed = $importService->parseFile($filePath);

            if (empty($parsed['headers']) || empty($parsed['rows'])) {
                $this->dispatch('showToast', message: __('import.file_empty'), type: 'danger');
                return;
            }

            $this->csvHeaders = $parsed['headers'];
            $this->rawRows = $parsed['rows'];
            $this->totalRows = $parsed['total_rows'];

            // Auto-map fields
            $suggestions = $importService->autoMapFields($this->csvHeaders, $this->entitySlug);
            $this->autoMappingSuggestions = $suggestions;

            // Set initial mapping from suggestions
            $this->fieldMapping = [];
            foreach ($suggestions as $csvIdx => $suggestion) {
                $this->fieldMapping[$csvIdx] = $suggestion['field'] ?? '';
            }

            // Get available fields for dropdown
            $registry = app(AdapterRegistry::class);
            $adapter = $registry->getAdapter($this->entitySlug);
            if ($adapter) {
                $this->availableFields = collect($adapter->getFieldDefinitions())
                    ->map(fn($def) => [
                        'field' => $def['field'],
                        'label' => $def['label'] ?? $def['field'],
                        'required' => $def['required'] ?? false,
                        'type' => $def['type'] ?? 'string',
                    ])
                    ->toArray();
            }

            // Check unmapped required fields
            $fieldMappingService = app(FieldMappingService::class);
            $this->unmappedRequired = $fieldMappingService->getUnmappedRequiredFields($suggestions, $adapter);

            $this->currentStep = 2;

        } catch (\Exception $e) {
            $this->dispatch('showToast', message: __('import.file_parse_error', ['error' => $e->getMessage()]), type: 'danger');
        }
    }

    // ─── Step 2 Methods ────────────────────────────────────────────────

    public function proceedToPreview()
    {
        // Verify required fields are mapped
        $mappedFields = array_filter($this->fieldMapping, fn($v) => !empty($v));
        $registry = app(AdapterRegistry::class);
        $adapter = $registry->getAdapter($this->entitySlug);

        if ($adapter) {
            foreach ($adapter->getFieldDefinitions() as $def) {
                if (($def['required'] ?? false) && !in_array($def['field'], $mappedFields)) {
                    // Check if it can be resolved from context
                    $canResolveFromContext = false;
                    if ($def['field'] === 'company' && $this->selectedCompanyId) {
                        $canResolveFromContext = true;
                    }
                    if ($def['field'] === 'department' && $this->selectedDepartmentId) {
                        $canResolveFromContext = true;
                    }
                    if (!$canResolveFromContext) {
                        $this->dispatch('showToast', message: __('import.required_field_unmapped', ['field' => $def['label'] ?? $def['field']]), type: 'danger');
                        return;
                    }
                }
            }
        }

        try {
            $importService = app(ImportService::class);
            $context = $this->buildContext();

            $preview = $importService->preview(
                $this->rawRows,
                $this->fieldMapping,
                $this->entitySlug,
                $context,
                20 // Preview first 20 rows
            );

            $this->previewData = $preview['rows'] ?? [];
            $this->previewValidCount = $preview['total_valid'] ?? 0;
            $this->previewErrorCount = $preview['total_errors'] ?? 0;
            $this->previewErrors = $preview['errors'] ?? [];

            $this->currentStep = 3;

        } catch (\Exception $e) {
            $this->dispatch('showToast', message: __('import.preview_error', ['error' => $e->getMessage()]), type: 'danger');
        }
    }

    // ─── Step 3 Methods ────────────────────────────────────────────────

    public function executeImport()
    {
        $this->isImporting = true;

        try {
            $context = $this->buildContext();

            // Resolve the uploaded file path — use the value captured at upload time.
            // Fallback: scan the imports directory if the property was somehow not set
            // (e.g. component was re-hydrated from a stale snapshot).
            $resolvedFilePath = $this->storedFilePath
                ?? ('imports/' . collect(Storage::disk('local')->files('imports'))
                    ->filter(fn ($f) => str_contains($f, $this->file->getClientOriginalName()))
                    ->last());

            // Create ImportJob record
            $importJob = ImportJob::create([
                'import_type' => $this->entitySlug,
                'user_id' => auth()->id(),
                'company_id' => $this->selectedCompanyId,
                'department_id' => $this->selectedDepartmentId,
                'file_name' => $this->file->getClientOriginalName(),
                'file_path' => $resolvedFilePath,
                'status' => ImportJob::STATUS_PENDING,
                'total_rows' => $this->totalRows,
                'import_config' => [
                    'entity_type' => $this->entitySlug,
                    'import_mode' => $this->importMode,
                    'auto_create_entities' => $this->autoCreateEntities,
                    'send_welcome_emails' => $this->sendWelcomeEmails,
                    'field_mapping' => $this->fieldMapping,
                    'context' => $context,
                ],
            ]);

            $this->importJobId = $importJob->id;

            // For small files, process synchronously
            if ($this->totalRows <= 100) {
                $importService = app(ImportService::class);
                $user = auth()->user();

                $result = $importService->execute(
                    $this->rawRows,
                    $this->fieldMapping,
                    $this->entitySlug,
                    $context,
                    $this->importMode,
                    $this->autoCreateEntities,
                    $user,
                    $importJob,
                    null,
                    $this->sendWelcomeEmails
                );

                $this->importResult = $result;
                $this->processedRows = $result['stats']['total'] ?? 0;
                $this->currentStep = 4;
                $this->isImporting = false;

            } else {
                // Dispatch background job for large files
                $filePath = $resolvedFilePath;

                ProcessAdapterImportJob::dispatch(
                    $this->entitySlug,
                    $filePath,
                    auth()->id(),
                    $this->fieldMapping,
                    $context,
                    $this->importMode,
                    $this->autoCreateEntities,
                    $this->sendWelcomeEmails,
                    $importJob->id
                );

                $this->currentStep = 4;
                $this->isImporting = true;
                $this->dispatch('showToast', message: __('import.job_queued'), type: 'success');
            }

        } catch (\Exception $e) {
            $this->isImporting = false;
            $this->dispatch('showToast', message: __('import.execution_error', ['error' => $e->getMessage()]), type: 'danger');
        }
    }

    public function refreshImportStatus()
    {
        if (!$this->importJobId) {
            return;
        }

        $job = ImportJob::find($this->importJobId);
        if (!$job) {
            return;
        }

        $this->processedRows = $job->processed_rows;
        $this->totalRows = $job->total_rows;

        if (in_array($job->status, [ImportJob::STATUS_COMPLETED, ImportJob::STATUS_FAILED])) {
            $this->isImporting = false;
            $this->importResult = [
                'stats' => [
                    'total' => $job->total_rows,
                    'created' => $job->successful_imports,
                    'errors' => $job->failed_imports,
                ],
                'errors' => $job->error_details ?? [],
            ];
        }
    }

    // ─── Navigation ────────────────────────────────────────────────────

    public function goToStep(int $step)
    {
        if ($step < $this->currentStep && $step >= 1) {
            $this->currentStep = $step;
        }
    }

    public function downloadTemplate()
    {
        if (!$this->entitySlug) {
            $this->dispatch('showToast', message: __('import.select_entity_type'), type: 'danger');
            return;
        }

        try {
            $importService = app(ImportService::class);
            $template = $importService->getTemplate($this->entitySlug);

            $csv = implode(',', $template['headers']) . "\n" . implode(',', $template['sample']) . "\n";

            return response()->streamDownload(function () use ($csv) {
                echo $csv;
            }, $template['filename'], [
                'Content-Type' => 'text/csv',
            ]);
        } catch (\Exception $e) {
            $this->dispatch('showToast', message: $e->getMessage(), type: 'danger');
        }
    }

    public function resetWizard()
    {
        $this->currentStep = 1;
        $this->file = null;
        $this->storedFilePath = null;
        $this->csvHeaders = [];
        $this->fieldMapping = [];
        $this->rawRows = [];
        $this->previewData = [];
        $this->previewErrors = [];
        $this->importResult = [];
        $this->isImporting = false;
        $this->importJobId = null;
        $this->processedRows = 0;
        $this->totalRows = 0;
    }

    // ─── Private Helpers ───────────────────────────────────────────────

    protected function buildContext(): array
    {
        return array_filter([
            'company_id' => $this->selectedCompanyId,
            'department_id' => $this->selectedDepartmentId,
            'service_id' => $this->selectedServiceId,
        ]);
    }

    public function render()
    {
        return view('livewire.import-wizard')->layout('components.layouts.dashboard');
    }
}
