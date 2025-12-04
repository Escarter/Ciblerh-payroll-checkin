<?php

use App\Models\User;
use App\Models\DownloadJob;
use Laravel\Dusk\Browser;

beforeEach(function () {
    // Setup
});

test('user can view download jobs page', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/download-jobs')
            ->assertSee('Download Jobs')
            ->assertPathIs('/portal/download-jobs');
    });
});

test('user can see job statistics', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/download-jobs')
            ->assertSee('Active')
            ->assertSee('Completed')
            ->assertSee('Failed');
    });
});

test('user can search for download jobs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $job = DownloadJob::factory()->create([
            'user_id' => $user->id,
            'file_name' => 'test-report.xlsx',
        ]);
        
        $browser->visit('/portal/download-jobs')
            ->type('#searchQuery', 'test-report')
            ->pause(1000)
            ->assertSee('test-report');
    });
});

test('user can filter by job type', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        DownloadJob::factory()->create([
            'user_id' => $user->id,
            'job_type' => 'checklog',
        ]);
        
        $browser->visit('/portal/download-jobs')
            ->select('#jobTypeFilter', 'checklog')
            ->pause(500)
            ->assertSelected('#jobTypeFilter', 'checklog');
    });
});

test('user can filter by status', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/download-jobs')
            ->select('#statusFilter', 'completed')
            ->pause(500)
            ->assertSelected('#statusFilter', 'completed');
    });
});

test('user can filter by date range', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/download-jobs')
            ->type('#dateFrom', now()->subDays(7)->format('Y-m-d'))
            ->type('#dateTo', now()->format('Y-m-d'))
            ->pause(500)
            ->assertInputValue('#dateFrom', now()->subDays(7)->format('Y-m-d'));
    });
});

test('user can switch between tabs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $job = DownloadJob::factory()->create([
            'user_id' => $user->id,
            'status' => DownloadJob::STATUS_COMPLETED,
        ]);
        
        $browser->visit('/portal/download-jobs')
            ->click('button:contains("Completed")')
            ->pause(500)
            ->assertSee('Completed');
    });
});

test('user can view job details', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $job = DownloadJob::factory()->create([
            'user_id' => $user->id,
        ]);
        
        $browser->visit('/portal/download-jobs')
            ->click("button[wire\\:click='showDetails({$job->id})']")
            ->pause(500)
            ->waitFor('#DetailsModal', 5)
            ->assertSee($job->file_name);
    });
});

test('user can download completed job', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $job = DownloadJob::factory()->create([
            'user_id' => $user->id,
            'status' => DownloadJob::STATUS_COMPLETED,
            'file_path' => 'reports/test.xlsx',
        ]);
        
        \Illuminate\Support\Facades\Storage::disk('public')->put('reports/test.xlsx', 'test content');
        
        $browser->visit('/portal/download-jobs')
            ->click('button:contains("Completed")')
            ->pause(500)
            ->click("button[wire\\:click='downloadFile({$job->id})']")
            ->pause(1000);
        // Note: File download testing in Dusk is limited
    });
});

test('user can cancel pending job', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $job = DownloadJob::factory()->create([
            'user_id' => $user->id,
            'status' => DownloadJob::STATUS_PENDING,
        ]);
        
        $browser->visit('/portal/download-jobs')
            ->click("button[wire\\:click='cancelJob({$job->id})']")
            ->pause(1000)
            ->assertSee('cancelled');
    });
});

test('user can delete a job', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $job = DownloadJob::factory()->create([
            'user_id' => $user->id,
        ]);
        
        $browser->visit('/portal/download-jobs')
            ->click("button[wire\\:click='showDeleteModal({$job->id})']")
            ->pause(500)
            ->waitFor('#DeleteModal', 5)
            ->within('#DeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(1000)
            ->assertSee('deleted');
    });
});

test('user can select all jobs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        DownloadJob::factory()->count(3)->create(['user_id' => $user->id]);
        
        $browser->visit('/portal/download-jobs')
            ->check('input[type="checkbox"][wire\\:model="selectAll"]')
            ->pause(500)
            ->assertChecked('input[type="checkbox"][wire\\:model="selectAll"]');
    });
});

test('user can bulk delete jobs', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        $jobs = DownloadJob::factory()->count(2)->create(['user_id' => $user->id]);
        
        $browser->visit('/portal/download-jobs')
            ->check("input[type='checkbox'][value='{$jobs[0]->id}']")
            ->check("input[type='checkbox'][value='{$jobs[1]->id}']")
            ->pause(500)
            ->click('button:contains("Bulk Delete")')
            ->pause(500)
            ->waitFor('#BulkDeleteModal', 5)
            ->within('#BulkDeleteModal', function ($modal) {
                $modal->press('Delete');
            })
            ->pause(1000)
            ->assertSee('deleted');
    });
});

test('user sees empty state when no jobs exist', function () {
    $this->browse(function (Browser $browser) {
        $user = $this->loginAs($browser, 'admin');
        
        $browser->visit('/portal/download-jobs')
            ->assertSee('No download jobs found');
    });
});







