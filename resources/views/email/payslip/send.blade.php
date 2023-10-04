@component('mail::message')
# {{__('Dear')}} {{$employee->name}},

{{__('Please find your pay slip attached,')}}<br>
{{__('How to open your pay slip')}}:<br>

{{__('Download the PDF document attached to the email. You will be asked for your password')}}<br>
{{__('Enter the password received by SMS')}}<br>

{{__('In case of difficulty, please call us or write to us using the contact details below')}}:<br>

{{__('Call and text')}}: <a href="tel:"></a><br>
Mail: <a href="mailto:helpdesk.">helpdesk.</a><br>
@endcomponent