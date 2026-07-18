@extends('layouts.app')
@section('bare', '1')
@section('title', 'غير مسموح')
@section('content')
    <x-error-page code="403" icon="alert" color="from-slate-500 to-slate-700"
        title="الصفحة دي مش متاحة ليك"
        message="مش مسموح لك تدخل هنا." />
@endsection
