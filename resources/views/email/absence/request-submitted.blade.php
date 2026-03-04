<x-mail::message>
@if($locale === 'en')
# New Leave of Absence Request

Dear {{ $supervisor->name }},

An employee has submitted a leave of absence request that requires your attention.

**Employee:** {{ $employee->name }}
@if($startDate && $endDate)
**Period:** {{ $startDate }} to {{ $endDate }}
@else
**Date:** {{ $absence->absence_date->format('Y-m-d') }}
@endif
**Reason:** {{ $absence->absence_reason }}

Please log in to the portal to review and approve or reject this request.

@else
# Nouvelle demande d'absence

Cher(e) {{ $supervisor->name }},

Un employé a soumis une demande d'absence qui nécessite votre attention.

**Employé:** {{ $employee->name }}
@if($startDate && $endDate)
**Période:** {{ $startDate }} à {{ $endDate }}
@else
**Date:** {{ $absence->absence_date->format('d/m/Y') }}
@endif
**Motif:** {{ $absence->absence_reason }}

Veuillez vous connecter au portail pour examiner et approuver ou rejeter cette demande.

@endif

Thanks / Merci,<br>
{{ config('app.name') }}
</x-mail::message>
