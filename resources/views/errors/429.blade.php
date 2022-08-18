@extends('errors::minimal')

@section('title', trans('errors.too_many_requests'))
@section('code', '429')
@section('message', trans('errors.too_many_requests'))
