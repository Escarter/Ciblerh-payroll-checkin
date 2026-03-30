@component('mail::message')
@php
	$mailContent = $content ?? null;

	// Backward-compatibility fallback for any legacy callers
	if ($mailContent === null && isset($message) && is_string($message)) {
		$mailContent = $message;
	}
@endphp

{!! $mailContent ?? '' !!}
@endcomponent