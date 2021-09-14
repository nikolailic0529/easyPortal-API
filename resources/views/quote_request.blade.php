@component('mail::message')
# {{ config('app.name') }}

A new quote request from organization {{ $request->organization->name }}

Oem: {{ $request->oem->name }}<br>
customer: {{ $request->customer ? $request->customer->name : $request->customer_name }}<br>
type: {{ $request->type->name }}<br>
<b>Contact Info:</b><br>
name: <span>{{ $request->contact->name }}</span><br>
email: <span>{{ $request->contact->email }}</span><br>
phone: <span>{{ $request->contact->phone_number }}</span><br>
@if (!empty($request->assets))
<b>Assets:</b>
@component('mail::table')
| prodcut name | service level | duration |
| :----------: | :------------:| :--------:|
@foreach($request->assets as $asset)
|{{ $asset->asset->product->name }}| {{ $asset->serviceLevel->name }} | {{ $asset->duration->name }}
@endforeach
@endcomponent
@endif

@if($request->message)
<b>Message:</b><br>
{!! $request->message !!}
@endif

Thanks,<br>
Support Team<br>
{{ config('app.name') }}
@endcomponent
