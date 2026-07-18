@extends('layouts.app')

@section('title', 'لوحة المشرف')

@section('content')
    <h1 class="text-xl font-bold mb-1">لوحة المشرف</h1>
    <p class="text-sm text-slate-500 mb-4">أدوات الإدارة في مكان واحد.</p>

    <div class="space-y-3">
        <a href="{{ route('reports.admin') }}" class="flex items-center gap-4 bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 active:scale-[.99] transition p-4">
            <div class="w-11 h-11 grid place-items-center rounded-2xl bg-rail-50 text-rail-600 shrink-0"><x-icon name="flag" class="w-6 h-6"/></div>
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-sm">البلاغات</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ number_format($newReports) }} جديد · {{ number_format($totalReports) }} إجمالي</p>
            </div>
            <x-icon name="chevron-right" class="w-5 h-5 text-slate-300 shrink-0"/>
        </a>

        <a href="{{ route('promos.admin') }}" class="flex items-center gap-4 bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 active:scale-[.99] transition p-4">
            <div class="w-11 h-11 grid place-items-center rounded-2xl bg-amber-50 text-amber-600 shrink-0"><x-icon name="star" class="w-6 h-6"/></div>
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-sm">العروض والبانرات</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ number_format($activePromos) }} عرض مفعّل</p>
            </div>
            <x-icon name="chevron-right" class="w-5 h-5 text-slate-300 shrink-0"/>
        </a>

        <a href="{{ route('sync') }}" class="flex items-center gap-4 bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 active:scale-[.99] transition p-4">
            <div class="w-11 h-11 grid place-items-center rounded-2xl bg-sky-50 text-sky-600 shrink-0"><x-icon name="refresh" class="w-6 h-6"/></div>
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-sm">مزامنة الأسعار</h3>
                <p class="text-xs text-slate-500 mt-0.5">جلب أسعار القطارات من نظام الهيئة</p>
            </div>
            <x-icon name="chevron-right" class="w-5 h-5 text-slate-300 shrink-0"/>
        </a>

        <a href="{{ route('admin.users') }}" class="flex items-center gap-4 bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 active:scale-[.99] transition p-4">
            <div class="w-11 h-11 grid place-items-center rounded-2xl bg-emerald-50 text-emerald-600 shrink-0"><x-icon name="seat" class="w-6 h-6"/></div>
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-sm">المستخدمين — إظهار المقاعد</h3>
                <p class="text-xs text-slate-500 mt-0.5">{{ number_format($seatsUsers) }} حساب مفعّل له المقاعد</p>
            </div>
            <x-icon name="chevron-right" class="w-5 h-5 text-slate-300 shrink-0"/>
        </a>
    </div>
@endsection
