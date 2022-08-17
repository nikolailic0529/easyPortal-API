@extends('errors::minimal')

@section('title', trans('errors.forbidden'))
@section('code', '403')
@section('message', trans('errors.forbidden'))
