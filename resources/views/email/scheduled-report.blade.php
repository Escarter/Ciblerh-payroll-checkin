@component('mail::message')
# {{__('scheduled_reports.email_greeting')}}

{{__('scheduled_reports.email_body', ['name' => $scheduledReport->name])}}

**{{__('scheduled_reports.report_type')}}:** {{$scheduledReport->job_type_display}}  
**{{__('scheduled_reports.generated_at')}}:** {{now()->format('F d, Y H:i')}}

@component('mail::button', ['url' => '#'])
{{__('scheduled_reports.download_report')}}
@endcomponent

{{__('scheduled_reports.email_footer')}}

{{__('common.thanks')}},<br>
{{config('app.name')}}
@endcomponent


