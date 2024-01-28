@component('mail::message')
{!! $message !!}
@endcomponent

@component('mail::message')
# @lang('Welcome Mr / Mrs') {{$employee->name}}

@lang('Your checking account has been created and you can now login into the employee portal at') [@lang('here')](https://checkin.ciblerh-emploi.com). @lang('your credential')
<br>
<br>
@lang('Email'): **{{$employee->email}}** <br>
@lang('Password'): **{{$password}}** <br>

@lang('In case of any difficulties, Contact your support via') ['HELP DESK CIBLE RH'](mailto:config('ciblerh.regards.company_support_email'))


@lang('Regards'),<br>
@lang(config('ciblerh.regards.company_name')) <br>

@component('mail::subcopy')
@lang(config('ciblerh.regards.additional_text'))
@endcomponent
@endcomponent