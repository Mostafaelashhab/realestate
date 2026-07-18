@extends('layouts.app')
@section('bare', '1')
@section('title', 'صيانة')
@section('content')
    <x-error-page icon="refresh" color="from-amber-500 to-amber-600"
        title="بنعمل صيانة سريعة"
        message="الموقع بيتحدّث دلوقتي — ارجع بعد دقايق." />
@endsection
