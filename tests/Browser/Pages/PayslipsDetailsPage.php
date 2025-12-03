<?php

namespace Tests\Browser\Pages;

use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;

class PayslipsDetailsPage extends Page
{
    protected $processId;

    public function __construct($processId)
    {
        $this->processId = $processId;
    }

    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return "/portal/payslips/{$this->processId}/details";
    }

    /**
     * Assert that the browser is on the page.
     */
    public function assert(Browser $browser): void
    {
        $browser->assertPathIs($this->url());
    }

    /**
     * Get the element shortcuts for the page.
     *
     * @return array<string, string>
     */
    public function elements(): array
    {
        return [
            '@searchInput' => '#search',
            '@orderBySelect' => '#orderBy',
            '@orderDirectionSelect' => '#direction',
            '@perPageSelect' => '#perPage',
            '@activeTab' => 'button:contains("Active")',
            '@deletedTab' => 'button:contains("Deleted")',
            '@selectAllCheckbox' => 'input[type="checkbox"][wire\\:model="selectAll"]',
            '@bulkResendButton' => 'button:contains("Resend All Failed")',
            '@bulkDeleteButton' => 'button:contains("Bulk Delete")',
            '@bulkRestoreButton' => 'button:contains("Bulk Restore")',
            '@unmatchedToggle' => 'button:contains("Unmatched")',
        ];
    }

    /**
     * Search for payslips
     */
    public function search(Browser $browser, string $query): void
    {
        $browser->type('@searchInput', $query)
            ->pause(500); // Wait for Livewire to process
    }

    /**
     * Switch to active tab
     */
    public function switchToActiveTab(Browser $browser): void
    {
        $browser->click('button:contains("Active")')
            ->pause(300);
    }

    /**
     * Switch to deleted tab
     */
    public function switchToDeletedTab(Browser $browser): void
    {
        $browser->click('button:contains("Deleted")')
            ->pause(300);
    }

    /**
     * Select all payslips
     */
    public function selectAll(Browser $browser): void
    {
        $browser->check('@selectAllCheckbox')
            ->pause(300);
    }

    /**
     * Click bulk resend failed button
     */
    public function clickBulkResendFailed(Browser $browser): void
    {
        $browser->click('button:contains("Resend All Failed")')
            ->pause(500);
    }

    /**
     * Confirm bulk resend in modal
     */
    public function confirmBulkResend(Browser $browser): void
    {
        $browser->waitFor('#BulkResendFailedModal', 5)
            ->within('#BulkResendFailedModal', function ($modal) {
                $modal->press('Resend All Failed');
            })
            ->pause(1000); // Wait for action to complete
    }

    /**
     * Toggle unmatched employees view
     */
    public function toggleUnmatched(Browser $browser): void
    {
        $browser->click('button:contains("Unmatched")')
            ->pause(500);
    }
}

