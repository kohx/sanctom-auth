@extends('layouts.mail')

@section('title', __('password reset'))

@section('content')
<div>
    <div>{{ __('password reset') }}</div>

    <a href='{{$url}}'>{{ __('click this link to go to password reset.') }}</a>
</div>
@endsection
