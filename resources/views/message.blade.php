@component('mail::message')
# {{ config('app.name') }}

A new Contact Us request from {{ $from }}.

-----

@html($message)


-----


Thanks,<br>
Support Team<br>
{{ config('app.name') }}
@endcomponent
