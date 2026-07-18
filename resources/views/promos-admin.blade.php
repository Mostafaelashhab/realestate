@extends('layouts.app')

@section('title', 'إدارة العروض')

@section('content')
    <h1 class="text-xl font-bold mb-1">العروض والبانرات</h1>
    <p class="text-sm text-slate-500 mb-4">بانرات تظهر للمستخدمين في الصفحة الرئيسية.</p>

    @if (session('status'))
        <div class="bg-emerald-50 text-emerald-800 text-sm rounded-2xl px-4 py-3 mb-4 flex items-center gap-2">
            <x-icon name="check" class="w-5 h-5 shrink-0"/> {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 text-red-700 text-sm rounded-2xl px-4 py-3 mb-4">{{ $errors->first() }}</div>
    @endif

    {{-- إضافة عرض --}}
    <form action="{{ route('promos.store') }}" method="POST" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 space-y-3 mb-5">
        @csrf
        <h2 class="font-bold text-sm">عرض جديد</h2>
        <input name="title" required maxlength="120" placeholder="العنوان" value="{{ old('title') }}"
            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rail-500/30">
        <input name="body" maxlength="200" placeholder="نص فرعي (اختياري)" value="{{ old('body') }}"
            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rail-500/30">
        <input name="url" type="url" placeholder="رابط عند الضغط (اختياري)" value="{{ old('url') }}"
            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rail-500/30">
        <div class="flex gap-3 flex-wrap">
            <select name="variant" class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rail-500/30">
                @foreach (\App\Models\Promo::VARIANTS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <input name="sort" type="number" min="0" placeholder="ترتيب" value="0"
                class="w-24 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-rail-500/30">
        </div>
        <div class="flex gap-3 flex-wrap text-sm">
            <label class="flex-1 min-w-[8rem]">من <input name="starts_at" type="date" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 mt-1"></label>
            <label class="flex-1 min-w-[8rem]">إلى <input name="ends_at" type="date" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 mt-1"></label>
        </div>
        <button class="w-full bg-rail-600 hover:bg-rail-700 text-white font-bold rounded-2xl px-4 py-3 transition">إضافة</button>
    </form>

    {{-- القائمة --}}
    @php
        $variantStyles = [
            'rail' => 'bg-rail-50 text-rail-800 ring-rail-200',
            'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
            'sky' => 'bg-sky-50 text-sky-800 ring-sky-200',
        ];
    @endphp

    @forelse ($promos as $promo)
        <div class="rounded-3xl ring-1 p-4 mb-3 {{ $variantStyles[$promo->variant] ?? $variantStyles['rail'] }} {{ $promo->active ? '' : 'opacity-50' }}">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-bold">{{ $promo->title }}</p>
                    @if ($promo->body)<p class="text-sm mt-0.5">{{ $promo->body }}</p>@endif
                    @if ($promo->url)<p class="text-xs mt-1 truncate opacity-70">{{ $promo->url }}</p>@endif
                    <p class="text-[11px] mt-1 opacity-70">
                        {{ $promo->active ? 'مفعّل' : 'متوقف' }}
                        @if ($promo->starts_at) · من {{ $promo->starts_at->format('Y/m/d') }} @endif
                        @if ($promo->ends_at) · إلى {{ $promo->ends_at->format('Y/m/d') }} @endif
                    </p>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <form action="{{ route('promos.toggle', $promo->id) }}" method="POST">
                        @csrf
                        <button class="text-xs font-bold bg-white/70 rounded-lg px-3 py-1.5">{{ $promo->active ? 'إيقاف' : 'تفعيل' }}</button>
                    </form>
                    <form action="{{ route('promos.destroy', $promo->id) }}" method="POST"
                        onsubmit="return confirm('حذف العرض؟')">
                        @csrf @method('DELETE')
                        <button class="text-xs font-bold bg-white/70 text-red-600 rounded-lg px-3 py-1.5">حذف</button>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-8 text-center text-slate-500">مفيش عروض.</div>
    @endforelse
@endsection
