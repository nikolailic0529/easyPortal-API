@component('mail::message')
# {{ config('app.name') }}

A new asset change request from {{ $request->from }} to change asset {{ $request->asset_id }}.

{{ $request->message }}

Thanks,<br>
Support Team<br>
{{ config('app.name') }}
@endcomponent
