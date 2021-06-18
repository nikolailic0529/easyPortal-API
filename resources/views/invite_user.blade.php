@component('mail::message')
# EasyPortal

Your have been invited to use EasyPortal dashboard.

@component('mail::button', ['url' => $url])
View EasyPortal
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent