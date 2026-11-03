<x-mail::message>
@if($approved)
@if($locale === 'en')
# Absence Request Approved

Dear {{ $user->name }},

Your absence request has been approved.

**Date:** {{ $absence->absence_date->format('Y-m-d') }}
**Reason:** {{ $absence->absence_reason }}

@else
# Demande d'absence approuvée

Cher(e) {{ $user->name }},

Votre demande d'absence a été approuvée.

**Date:** {{ $absence->absence_date->format('d/m/Y') }}
**Motif:** {{ $absence->absence_reason }}

@endif
@else
@if($locale === 'en')
# Absence Request Rejected

Dear {{ $user->name }},

Your absence request has been rejected.

**Date:** {{ $absence->absence_date->format('Y-m-d') }}
**Reason:** {{ $absence->absence_reason }}
@if($absence->approval_reason)
**Comment:** {{ $absence->approval_reason }}
@endif

@else
# Demande d'absence rejetée

Cher(e) {{ $user->name }},

Votre demande d'absence a été rejetée.

**Date:** {{ $absence->absence_date->format('d/m/Y') }}
**Motif:** {{ $absence->absence_reason }}
@if($absence->approval_reason)
**Commentaire:** {{ $absence->approval_reason }}
@endif

@endif
@endif

Thanks / Merci,<br>
{{ config('app.name') }}
</x-mail::message>
