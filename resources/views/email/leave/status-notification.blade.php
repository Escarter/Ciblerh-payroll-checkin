<x-mail::message>
@if($approved)
@if($locale === 'en')
# Leave Request Approved

Dear {{ $user->name }},

Your leave request has been approved.

**Leave Type:** {{ $leave->leaveType->name ?? 'N/A' }}
**Start Date:** {{ $leave->start_date->format('Y-m-d') }}
**End Date:** {{ $leave->end_date?->format('Y-m-d') ?? 'N/A' }}
**Reason:** {{ $leave->leave_reason }}

@else
# Demande de congé approuvée

Cher(e) {{ $user->name }},

Votre demande de congé a été approuvée.

**Type de congé:** {{ $leave->leaveType->name ?? 'N/A' }}
**Date de début:** {{ $leave->start_date->format('d/m/Y') }}
**Date de fin:** {{ $leave->end_date?->format('d/m/Y') ?? 'N/A' }}
**Motif:** {{ $leave->leave_reason }}

@endif
@else
@if($locale === 'en')
# Leave Request Rejected

Dear {{ $user->name }},

Your leave request has been rejected.

**Leave Type:** {{ $leave->leaveType->name ?? 'N/A' }}
**Start Date:** {{ $leave->start_date->format('Y-m-d') }}
**End Date:** {{ $leave->end_date?->format('Y-m-d') ?? 'N/A' }}
**Reason:** {{ $leave->leave_reason }}
@if($leave->manager_approval_reason)
**Manager Comment:** {{ $leave->manager_approval_reason }}
@endif

@else
# Demande de congé rejetée

Cher(e) {{ $user->name }},

Votre demande de congé a été rejetée.

**Type de congé:** {{ $leave->leaveType->name ?? 'N/A' }}
**Date de début:** {{ $leave->start_date->format('d/m/Y') }}
**Date de fin:** {{ $leave->end_date?->format('d/m/Y') ?? 'N/A' }}
**Motif:** {{ $leave->leave_reason }}
@if($leave->manager_approval_reason)
**Commentaire du manager:** {{ $leave->manager_approval_reason }}
@endif

@endif
@endif

Thanks / Merci,<br>
{{ config('app.name') }}
</x-mail::message>
