@component('mail::message')
# {{ config('app.name') }}

Your have been invited to use {{ config('app.name') }} dashboard.

@component('mail::button', ['url' => $url])
Join
@endcomponent

Thanks,<br>
Support Team<br>
{{ config('app.name') }}
@endcomponent