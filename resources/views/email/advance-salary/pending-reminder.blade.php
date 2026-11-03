<x-mail::message>
@if($locale === 'en')
# Pending Advance Salary Requests

Dear {{ $recipient->name }},

This is a daily reminder that you have **{{ $pendingAdvanceSalaries->count() }}** advance salary request(s) pending your approval.

@else
# Demandes d'avance sur salaire en attente

Cher(e) {{ $recipient->name }},

Ceci est un rappel quotidien indiquant que vous avez **{{ $pendingAdvanceSalaries->count() }}** demande(s) d'avance sur salaire en attente de votre approbation.

@endif

@foreach($pendingAdvanceSalaries as $advanceSalary)
- **{{ $advanceSalary->user->name }}**: {{ number_format($advanceSalary->amount) }} XAF — {{ $advanceSalary->beneficiary_name }}
@endforeach

@if($locale === 'en')
Please log in to the portal to review these requests.
@else
Veuillez vous connecter au portail pour examiner ces demandes.
@endif

Thanks / Merci,<br>
{{ config('app.name') }}
</x-mail::message>
