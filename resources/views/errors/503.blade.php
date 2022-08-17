@extends('errors::minimal')

@section('title', trans('errors.service_unavailable'))
@section('code', '503')
@section('message', trans('errors.service_unavailable'))
