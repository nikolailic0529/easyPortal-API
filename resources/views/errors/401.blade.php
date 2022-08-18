@extends('errors::minimal')

@section('title', trans('errors.unauthorized'))
@section('code', '401')
@section('message', trans('errors.unauthorized'))
