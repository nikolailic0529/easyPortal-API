@component('mail::message')
# {{ config('app.name') }}

A new {{ $type }} change request from {{ $request->from }} to change {{ $title }}.

-----

@html($request->message)


-----


Thanks,<br>
Support Team<br>
{{ config('app.name') }}
@endcomponent
