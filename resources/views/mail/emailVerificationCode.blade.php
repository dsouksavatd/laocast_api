@extends('mail.layout')

@section('name',){{ @$params['name'] }}@endsection
@section('target'){{ @$params['target'] }}@endsection
@section('action'){{ @$params['action'] }}@endsection

@section('content_start')
{{ @$params['content_start'] }}
@endsection

@section('content_end')
{{ @$params['content_end'] }}
@endsection