@component('mail::message')
# {{ config('app.name') }}

You have been invited to join {{ config('app.name') }}.

@component('mail::button', ['url' => $url])
Join
@endcomponent

Thanks,<br>
Support Team<br>
{{ config('app.name') }}
@endcomponent
