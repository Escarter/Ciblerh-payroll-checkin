<x-mail::message>
@if($locale === 'en')
# New Advance Salary Request

Dear {{ $supervisor->name }},

An employee has submitted a new advance salary request that requires your attention.

**Employee:** {{ $employee->name }}
**Amount:** {{ number_format($advanceSalary->amount) }} XAF
**Reason:** {{ $advanceSalary->reason }}
**Repayment Month:** {{ $advanceSalary->repayment_from_month->format('F Y') }}
**Beneficiary:** {{ $advanceSalary->beneficiary_name }}

Please log in to the portal to review and approve or reject this request.

@else
# Nouvelle demande d'avance sur salaire

Cher(e) {{ $supervisor->name }},

Un employé a soumis une nouvelle demande d'avance sur salaire qui nécessite votre attention.

**Employé:** {{ $employee->name }}
**Montant:** {{ number_format($advanceSalary->amount) }} XAF
**Motif:** {{ $advanceSalary->reason }}
**Mois de remboursement:** {{ $advanceSalary->repayment_from_month->translatedFormat('F Y') }}
**Bénéficiaire:** {{ $advanceSalary->beneficiary_name }}

Veuillez vous connecter au portail pour examiner et approuver ou rejeter cette demande.

@endif

Thanks / Merci,<br>
{{ config('app.name') }}
</x-mail::message>
