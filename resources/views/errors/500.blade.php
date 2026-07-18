@extends('layouts.app')
@section('bare', '1')
@section('title', 'حصل خطأ')
@section('content')
    <x-error-page code="500" icon="alert" color="from-rose-500 to-rose-700"
        title="حصل خطأ عندنا"
        message="في مشكلة مؤقتة من عندنا. جرّب تاني بعد شوية." />
@endsection
