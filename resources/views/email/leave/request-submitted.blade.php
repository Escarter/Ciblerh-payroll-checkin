<x-mail::message>
@if($locale === 'en')
# New Leave Request

Dear {{ $supervisor->name }},

An employee has submitted a leave request that requires your attention.

**Employee:** {{ $employee->name }}
**Leave Type:** {{ $leave->leaveType?->name ?? '-' }}
**Period:** {{ $leave->start_date->format('Y-m-d') }} to {{ $leave->end_date->format('Y-m-d') }}
**Reason:** {{ $leave->leave_reason }}

Please log in to the portal to review and approve or reject this request.

@else
# Nouvelle demande de congé

Cher(e) {{ $supervisor->name }},

Un employé a soumis une demande de congé qui nécessite votre attention.

**Employé:** {{ $employee->name }}
**Type de congé:** {{ $leave->leaveType?->name ?? '-' }}
**Période:** {{ $leave->start_date->format('d/m/Y') }} à {{ $leave->end_date->format('d/m/Y') }}
**Motif:** {{ $leave->leave_reason }}

Veuillez vous connecter au portail pour examiner et approuver ou rejeter cette demande.

@endif

Thanks / Merci,<br>
{{ config('app.name') }}
</x-mail::message>
