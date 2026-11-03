<x-mail::message>
@if($locale === 'en')
# Advance Salary Request Pending Your Approval

Dear {{ $manager->name }},

An advance salary request has been approved by the supervisor and is now pending your approval.

**Employee:** {{ $employee->name }}
**Amount:** {{ number_format($advanceSalary->amount) }} XAF
**Reason:** {{ $advanceSalary->reason }}
**Beneficiary:** {{ $advanceSalary->beneficiary_name }}
**MoMo Number:** {{ $advanceSalary->beneficiary_mobile_money_number }}

Please log in to the portal to review and approve or reject this request.

@else
# Demande d'avance sur salaire en attente de votre approbation

Cher(e) {{ $manager->name }},

Une demande d'avance sur salaire a été approuvée par le superviseur et est en attente de votre approbation.

**Employé:** {{ $employee->name }}
**Montant:** {{ number_format($advanceSalary->amount) }} XAF
**Motif:** {{ $advanceSalary->reason }}
**Bénéficiaire:** {{ $advanceSalary->beneficiary_name }}
**Numéro MoMo:** {{ $advanceSalary->beneficiary_mobile_money_number }}

Veuillez vous connecter au portail pour examiner et approuver ou rejeter cette demande.

@endif

Thanks / Merci,<br>
{{ config('app.name') }}
</x-mail::message>
