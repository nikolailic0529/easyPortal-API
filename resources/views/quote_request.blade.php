@component('mail::message')
# {{ config('app.name') }}

A new quote request from organization {{ $request->organization->name }}

Oem: {{ $request->oem->name }}<br>
customer: {{ $request->customer->name }}<br>
type: {{ $request->type->name }}<br>
<b>Contact Info:</b><br>
name: {{ $request->contact->name }}<br>
email: {{ $request->contact->email }}<br>
phone: {{ $request->contact->phone }}<br>
<b>Assets:</b>
@component('mail::table')
| prodcut name | service level | duration |
| :----------: | :------------:| :--------:|
@foreach($request->assets as $asset)
|{{ $asset->asset->product->name }}| {{ $asset->serviceLevel->name }} | {{ $asset->duration->name }}
@endforeach
@endcomponent

@if($request->message)
<b>Message:</b><br>
{!! $request->message !!}
@endif

Thanks,<br>
Support Team<br>
{{ config('app.name') }}
@endcomponent
