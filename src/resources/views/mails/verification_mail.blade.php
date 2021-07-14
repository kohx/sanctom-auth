@extends('layouts.mail')

@section('title', __('registration certification'))

@section('content')
<div>
    <div>{{ __('registration certification') }}</div>

        <a href='{{$url}}'>{{ __('please click this link to verify your email.') }}</a>
</div>
@endsection
