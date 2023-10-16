<?php

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/


Route::get('language/{locale}', function ($locale) {
    app()->setLocale($locale);
    session()->put('locale', $locale);
    return redirect()->back();
})->name('language-switcher');

Route::get('/', function () {
    return redirect('login');
});


Auth::routes(['register'=>'false']);


Route::any('/logout', [LoginController::class, 'logout']);


Route::group(['prefix' => 'employee', 'middleware' => ['auth', 'role:employee']], function () {
    Route::get('/dashboard', App\Livewire\Employee\Dashboard::class)->name('employee.dashboard');

    Route::get('/profile', App\Livewire\Employee\Profile::class)->name('employee.profile');

    //AuditLogs
    Route::prefix('auditlogs')->group(function () {
        Route::get('/', App\Livewire\Employee\AuditLogs\Index::class)->name('employee.auditlogs');
    });


    Route::prefix('checklogs')->group(function(){
        Route::get('/', App\Livewire\Employee\Checklog\Index::class)->name('employee.checklogs');
    });

    Route::prefix('overtimes')->group(function () {
        Route::get('/', App\Livewire\Employee\Overtime\Index::class)->name('employee.overtimes');
    });

    //Advance Salary
    Route::prefix('advance-salaries')->group(function () {
        Route::get('/', App\Livewire\Employee\AdvanceSalary\Index::class)->name('employee.advance-salaries');
    });

    //Absences
    Route::prefix('absences')->group(function () {
        Route::get('/', App\Livewire\Employee\Absences\Index::class)->name('employee.absences');
    });

    //Payslip
    Route::prefix('payslips')->group(function () {
        Route::get('/', App\Livewire\Employee\Payslip\Index::class)->name('employee.payslips');
    });
    //Leaves
    Route::prefix('leaves')->group(function () {
        Route::get('/', App\Livewire\Employee\Leaves\Index::class)->name('employee.leaves');
    });

});


Route::group(
    ['prefix' => 'portal', 'middleware' => ['auth','role:supervisor|manager|admin']],function () {

        Route::get('/dashboard',App\Livewire\Portal\Dashboard\Index::class)->name('portal.dashboard');
        
        Route::get('/profile-setting', App\Livewire\Portal\ProfileSetting::class)->name('portal.profile-setting');
     
        //Companies
        Route::prefix('companies')->group(function () {
            Route::get('/', App\Livewire\Portal\Companies\Index::class)->name('portal.companies.index');
        });
        //Department
        Route::prefix('company')->group(function () {
            Route::get('/{company_uuid}/departments', App\Livewire\Portal\Departments\Index::class)->name('portal.departments.index');
        });
        //Department's employees
        Route::prefix('company')->group(function () {
            Route::get('/{company_uuid}/employees', App\Livewire\Portal\Employees\Index::class)->name('portal.employees.index');
        });
       
        //Company's employees
        Route::prefix('employees')->group(function () {
            Route::get('/all', App\Livewire\Portal\Employees\All::class)->name('portal.all-employees');
            Route::get('/payslip/{employee_uuid}/history', App\Livewire\Portal\Employees\Payslip\History::class)->name('portal.employee.payslips');
        });
        //Department's employees
        Route::prefix('department')->group(function () {
            Route::get('/{department_uuid}/services', App\Livewire\Portal\Services\Index::class)->name('portal.services.index');
        });
        
        //Overtime management
        Route::prefix('overtimes')->group(function () {
            Route::get('/', App\Livewire\Portal\Overtimes\Index::class)->name('portal.overtimes.index');
        });
        //Checklog management
        Route::prefix('checklogs')->group(function () {
            Route::get('/', App\Livewire\Portal\Checklogs\Index::class)->name('portal.checklogs.index');
        });
        //Checklog management
        Route::prefix('absences')->group(function () {
            Route::get('/', App\Livewire\Portal\Absences\Index::class)->name('portal.absences.index');
        });

        //Advance Salaries
        Route::prefix('advance-salaries')->group(function () {
            Route::get('/', App\Livewire\Portal\AdvanceSalaries\Index::class)->name('portal.advance-salaries.index');
        });
       

        //Payslip Salaries
        Route::prefix('payslips')->group(function () {
            Route::get('/', App\Livewire\Portal\Payslips\Index::class)->name('portal.payslips.index');
            Route::get('/{id}/details', App\Livewire\Portal\Payslips\Details::class)->name('portal.payslips.details');
            Route::get('/history', App\Livewire\Portal\Payslips\All::class)->name('portal.payslips.history');
        });

        //Leave Management
        Route::prefix('leaves')->group(function () {
            Route::get('/', App\Livewire\Portal\Leaves\Index::class)->name('portal.leaves.index');
            Route::get('/types', App\Livewire\Portal\Leaves\Types\Index::class)->name('portal.leaves.types');
        });

        //AuditLogs
        Route::prefix('auditlogs')->group(function () {
            Route::get('/', App\Livewire\Portal\AuditLogs\Index::class)->name('portal.auditlogs.index');
        });

        //roles
        Route::prefix('roles')->group(function () {
            Route::get('/', App\Livewire\Portal\Roles\Index::class)->name('portal.roles.index');
        });
        //Setting
        Route::prefix('settings')->group(function () {
            Route::get('/', App\Livewire\Portal\Settings\Index::class)->name('portal.settings.index');
        });

        //Checklog management
        Route::prefix('reports')->group(function () {
            Route::get('/checklogs', App\Livewire\Portal\Reports\Checklog::class)->name('portal.reports.checklogs');
            Route::get('/overtimes', App\Livewire\Portal\Reports\Overtime::class)->name('portal.reports.overtime');
            Route::get('/payslips', App\Livewire\Portal\Reports\Payslip::class)->name('portal.reports.payslip');
            
        });

        Route::get('/checkin-report-template',function(){
            $month = '2022-03';
            $start = Carbon::parse($month)->startOfMonth();
            $end = Carbon::parse($month)->endOfMonth();

            $dates = [];
            while ($start->lte($end)) {
                $dates[] = $start->copy();
                $start->addDay();
            }
            $users = User::whereHas('tickings', function ($ticking) use ($month, $dates) {
                $ticking->whereYear('start_time', explode('-', $month)[0])->whereMonth('start_time', explode('-', $month)[1])->orderBy('start_time', 'asc');
            })->get();

            return view('livewire.portal.reports.partials.checkin-report-template',[
                'dates'=>$dates,
                'month'=> explode('-', $month)[1],
                'users' => $users,
            ]);
        });
});