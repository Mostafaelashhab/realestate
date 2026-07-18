@extends('layouts.app')
@section('bare', '1')
@section('title', 'انتهت الجلسة')
@section('content')
    <x-error-page icon="clock" color="from-amber-500 to-amber-600"
        title="الجلسة انتهت"
        message="عدّى وقت طويل. حدّث الصفحة وحاول تاني.">
        <button type="button" onclick="location.reload()" class="mt-3 w-full inline-flex items-center justify-center gap-1.5 bg-white ring-1 ring-slate-200 text-rail-700 font-bold rounded-2xl px-4 py-3 hover:ring-rail-300 transition">
            <x-icon name="refresh" class="w-5 h-5" /> حدّث الصفحة
        </button>
    </x-error-page>
@endsection
