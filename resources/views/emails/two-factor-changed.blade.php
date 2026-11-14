<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <title>{{ __('two_factor.notify_heading') }}</title>
</head>
<body style="margin:0;padding:24px;background-color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
        <tr>
            <td style="padding:20px 24px;background:#1e3a8a;color:#ffffff;">
                <div style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#93c5fd;">{{ $appName }}</div>
                <div style="font-size:18px;font-weight:600;margin-top:4px;">{{ __('two_factor.notify_heading') }}</div>
            </td>
        </tr>
        <tr>
            <td style="padding:24px;font-size:14px;line-height:1.6;color:#334155;">
                <p style="margin:0 0 12px;">
                    {{ __('two_factor.mail_greeting_named', ['name' => $user->name]) }}
                </p>

                <p style="margin:0 0 16px;">
                    @switch($changeType)
                        @case('enabled')
                            {{ __('two_factor.notify_body_enabled', ['app' => $appName, 'method' => $method ?? '-']) }}
                            @break
                        @case('method_changed')
                            {{ __('two_factor.notify_body_method_changed', ['app' => $appName, 'method' => $method ?? '-']) }}
                            @break
                        @case('disabled')
                            {{ __('two_factor.notify_body_disabled', ['app' => $appName]) }}
                            @break
                        @case('reset')
                            {{ __('two_factor.notify_body_reset', ['app' => $appName]) }}
                            @break
                        @case('codes_regenerated')
                            {{ __('two_factor.notify_body_codes_regenerated', ['app' => $appName]) }}
                            @break
                        @default
                            {{ __('two_factor.notify_body_changed', ['app' => $appName]) }}
                    @endswitch
                </p>

                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 16px;font-size:13px;color:#475569;margin-bottom:16px;">
                    <tr>
                        <td style="padding:4px 0;font-weight:600;width:130px;">{{ __('two_factor.notify_when_label') }}</td>
                        <td style="padding:4px 0;">{{ $changedAt }}</td>
                    </tr>
                    @if($method)
                        <tr>
                            <td style="padding:4px 0;font-weight:600;">{{ __('two_factor.notify_method_label') }}</td>
                            <td style="padding:4px 0;">{{ $method }}</td>
                        </tr>
                    @endif
                    @if($ipAddress)
                        <tr>
                            <td style="padding:4px 0;font-weight:600;">{{ __('two_factor.notify_ip_label') }}</td>
                            <td style="padding:4px 0;">{{ $ipAddress }}</td>
                        </tr>
                    @endif
                    @if($userAgent)
                        <tr>
                            <td style="padding:4px 0;font-weight:600;">{{ __('two_factor.notify_device_label') }}</td>
                            <td style="padding:4px 0;word-break:break-word;">{{ \Illuminate\Support\Str::limit($userAgent, 140) }}</td>
                        </tr>
                    @endif
                </table>

                <p style="margin:0;color:#b91c1c;font-weight:500;">
                    {{ __('two_factor.notify_warning_line') }}
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
