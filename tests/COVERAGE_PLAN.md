# 100% Code Coverage Plan

## Current Status
- **Test Files Created**: 15
- **Test Cases**: 100+
- **Current Pass Rate**: ~30% (29 passing, 85 failing)
- **Target**: 100% coverage with all tests passing

## Systematic Approach

### Phase 1: Fix Existing Tests (Priority 1)
1. ✅ Fix Mail mocking issues
2. ✅ Fix Batch mocking issues  
3. ✅ Fix Factory relationships
4. ✅ Add Setting factory
5. Fix remaining test failures

### Phase 2: Complete Job Coverage (Priority 2)
- [ ] RenameEncryptPdfJob - PDF encryption, multi-page combining
- [ ] SplitPdfJob - PDF splitting by matricule
- [ ] SinglePayslipPlan - Single payslip processing
- [ ] ResendFailedPayslipPlan - Bulk resend planning
- [ ] All DownloadJobs (10 jobs)
- [ ] Single payslip jobs (4 jobs)

### Phase 3: Complete Livewire Coverage (Priority 3)
- [ ] Payslips/Index - Process listing
- [ ] Payslips/All - All payslips view
- [ ] Employees/Index - Employee management
- [ ] Employees/All - All employees
- [ ] Employees/Payslip/History - Employee payslip history
- [ ] Companies/Index - Company management
- [ ] Companies/AssignManager - Manager assignment
- [ ] Departments/Index - Department management
- [ ] Services/Index - Service management
- [ ] All other Livewire components (20+)

### Phase 4: Complete Model Coverage (Priority 4)
- [ ] All model relationships
- [ ] All model scopes
- [ ] All model accessors/mutators
- [ ] All model methods

### Phase 5: Complete Service Coverage (Priority 5)
- [ ] SMS Services (TwilioSMS, Nexah, AwsSnsSMS)
- [ ] SmsProvider service

### Phase 6: Complete Helper Coverage (Priority 6)
- [ ] All helper functions in Helpers.php
- [ ] All utility functions

### Phase 7: Integration Tests (Priority 7)
- [ ] End-to-end payslip sending workflow
- [ ] End-to-end PDF processing workflow
- [ ] End-to-end employee management workflow

## Test Execution Strategy

1. **Fix all failing tests first** - Ensure foundation is solid
2. **Add missing tests systematically** - One feature at a time
3. **Run coverage after each phase** - Track progress
4. **Refactor as needed** - Keep tests maintainable

## Files Requiring Tests

### Jobs (25 files)
- ✅ SendPayslipJob
- ✅ RetryPayslipEmailJob
- [ ] RenameEncryptPdfJob
- [ ] SplitPdfJob
- [ ] PayslipSendingPlan
- [ ] SinglePayslipPlan
- [ ] ResendFailedPayslipPlan
- [ ] All DownloadJobs (10)
- [ ] Single payslip jobs (4)

### Livewire Components (30+ files)
- ✅ Payslips/Details
- [ ] Payslips/Index
- [ ] Payslips/All
- [ ] Employees/Index
- [ ] Employees/All
- [ ] Employees/Payslip/History
- [ ] All other components

### Models (17 files)
- ✅ User
- ✅ Payslip
- ✅ Company
- ✅ Department
- ✅ SendPayslipProcess
- [ ] All other models

### Services (4 files)
- [ ] TwilioSMS
- [ ] Nexah
- [ ] AwsSnsSMS
- [ ] SmsProvider

### Helpers (1 file)
- [ ] Helpers.php (all functions)

## Estimated Test Count
- **Current**: ~100 tests
- **Target**: ~500+ tests for 100% coverage
- **Estimated Time**: Significant effort required

## Next Steps
1. Fix all 85 failing tests
2. Add tests for remaining Jobs
3. Add tests for remaining Livewire components
4. Add tests for remaining Models
5. Add tests for Services
6. Add tests for Helpers
7. Run coverage analysis
8. Fill any remaining gaps










