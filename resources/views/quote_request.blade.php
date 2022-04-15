<?php /** @var \App\Models\QuoteRequest $request */ ?>
@component('mail::message')
# {{ config('app.name') }}

A new quote request from organization {{ $request->organization->name }}

**Oem**: {{ $request->oem_custom ?: $request->oem->name }}<br>
**Customer**: {{ $request->customer_custom ?: $request->customer->name }}<br>
**Type**: {{ $request->type_custom ?: $request->type->name }}<br>

## Contact Info:

**Name**: {{ $request->contact->name }}<br>
**Email**: {{ $request->contact->email }}<br>
**Phone**: {{ $request->contact->phone_number }}<br>

@if (count($request->assets) > 0)
## Assets:
@component('mail::table')
| Product Name | Service Level | Duration   |
| ------------ | ------------- | :--------: |
@foreach($request->assets as $asset)
| {{ $asset->asset->product->name }} | {{ $asset->service_level_custom ?: $asset->serviceLevel->name }} | {{ $asset->duration->name }} |
@endforeach
@endcomponent
@endif

@if (count($request->documents) > 0)
## Documents:
@component('mail::table')
| Document    | Duration   |
| ----------- | :--------: |
@foreach($request->documents as $document)
| {{ $document->document->is_contract ? 'Contract' : 'Quote' }} {{ $document->document->number }} | {{ $document->duration->name }} |
@endforeach
@endcomponent
@endif

@if($request->message)
## Message:

-----

@html($request->message)


-----
@endif

Thanks,<br>
Support Team<br>
{{ config('app.name') }}
@endcomponent
