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
|{{ $asset->product->name }}| {{ $asset->pivot->serviceLevel->name }} | {{ $asset->pivot->duration->duration }}
@endforeach
@endcomponent


Thanks,<br>
Support Team<br>
{{ config('app.name') }}
@endcomponent
