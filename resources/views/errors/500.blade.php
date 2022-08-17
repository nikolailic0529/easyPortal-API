@extends('errors::minimal')

@section('title', trans('errors.server_error'))
@section('code', '500')
@section('message', trans('errors.server_error'))
