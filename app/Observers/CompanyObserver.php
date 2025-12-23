<?php

namespace App\Observers;

use App\Models\Company;

class CompanyObserver
{
    /**
     * Handle the Company "created" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function created(Company $company)
    {
       auditLog(
            auth()->user(),
            'company_created',
            'web',
            __('audit_logs.created_entity', ['entity' => 'company', 'name' => $company->name]),
            $company, // Pass model for enhanced tracking
            [], // No old values for creates
            $company->getAttributes(), // New values
            ['entity' => 'company'] // Metadata
        );
    }

    /**
     * Handle the Company "updated" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function updated(Company $company)
    {
       auditLog(
            auth()->user(),
            'company_updated',
            'web',
            __('audit_logs.updated_entity', ['entity' => 'company', 'name' => $company->name]),
            $company, // Pass model - changes will be auto-detected
            [], // Old values will be auto-detected from getOriginal()
            [], // New values will be auto-detected from getDirty()
            ['entity' => 'company'] // Metadata
        );
    }

    /**
     * Handle the Company "deleted" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function deleted(Company $company)
    {
       auditLog(
            auth()->user(),
            'company_deleted',
            'web',
            __('audit_logs.deleted_entity', ['entity' => 'company', 'name' => $company->name]),
            $company, // Pass model for enhanced tracking
            $company->getAttributes(), // Capture values before deletion
            [], // No new values for deletes
            ['entity' => 'company'] // Metadata
        );
    }

    /**
     * Handle the Company "restored" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function restored(Company $company)
    {
        //
    }

    /**
     * Handle the Company "force deleted" event.
     *
     * @param  \App\Models\Company  $company
     * @return void
     */
    public function forceDeleted(Company $company)
    {
        //
    }
}
