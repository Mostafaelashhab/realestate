@extends('layouts.app')

@section('title', 'بلّغ عن خطأ')

@section('content')
    <div class="flex items-center gap-2 mb-1">
        <x-icon name="flag" class="w-6 h-6 text-rail-700"/>
        <h1 class="text-xl font-bold">بلّغ عن خطأ</h1>
    </div>
    <p class="text-sm text-slate-500 mb-5">لقيت ميعاد أو سعر غلط، أو في مشكلة؟ بلّغنا ونصلّحها بأسرع وقت.</p>

    @if (session('status'))
        <div class="bg-emerald-50 text-emerald-800 text-sm rounded-2xl px-4 py-3 mb-4 flex items-center gap-2">
            <x-icon name="check" class="w-5 h-5 shrink-0"/>
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 text-red-700 text-sm rounded-2xl px-4 py-3 mb-4">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('report.store') }}" method="POST" class="bg-white rounded-3xl shadow-sm ring-1 ring-slate-100 p-4 space-y-4">
        @csrf

        {{-- نوع البلاغ --}}
        <div>
            <label class="block text-sm font-bold mb-2">نوع البلاغ</label>
            <div class="grid grid-cols-3 gap-2">
                @foreach (\App\Models\Report::TYPES as $key => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="type" value="{{ $key }}" class="peer sr-only"
                            {{ old('type', $type) === $key ? 'checked' : '' }}>
                        <span class="block text-center text-sm rounded-2xl border border-slate-200 bg-slate-50 px-2 py-3 font-medium
                            peer-checked:border-rail-600 peer-checked:bg-rail-50 peer-checked:text-rail-700 transition">
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- رقم القطار (اختياري) --}}
        <div>
            <label for="train_number" class="block text-sm font-bold mb-2">رقم القطار <span class="text-slate-400 font-normal">(اختياري)</span></label>
            <input type="text" name="train_number" id="train_number" inputmode="numeric"
                value="{{ old('train_number', $trainNumber) }}" placeholder="مثال: 948"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 focus:ring-2 focus:ring-rail-600 focus:border-rail-600 focus:bg-white transition">
        </div>

        {{-- وصف المشكلة --}}
        <div>
            <label for="message" class="block text-sm font-bold mb-2">وصف المشكلة</label>
            <textarea name="message" id="message" rows="4" required
                placeholder="اكتب تفاصيل الخطأ: الميعاد/السعر الصحيح، المحطة، أو أي ملاحظة…"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 focus:ring-2 focus:ring-rail-600 focus:border-rail-600 focus:bg-white transition">{{ old('message') }}</textarea>
        </div>

        {{-- وسيلة تواصل (اختياري) --}}
        <div>
            <label for="contact" class="block text-sm font-bold mb-2">وسيلة تواصل <span class="text-slate-400 font-normal">(اختياري)</span></label>
            <input type="text" name="contact" id="contact" value="{{ old('contact') }}"
                placeholder="إيميل أو رقم موبايل لو حابب نرد عليك"
                class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 focus:ring-2 focus:ring-rail-600 focus:border-rail-600 focus:bg-white transition">
        </div>

        <button type="submit"
            class="w-full flex items-center justify-center gap-2 bg-rail-600 hover:bg-rail-700 active:scale-[.99] text-white font-extrabold rounded-2xl px-4 py-3.5 transition shadow-lg shadow-rail-600/25">
            <x-icon name="send" class="w-5 h-5"/>
            إرسال البلاغ
        </button>
    </form>
@endsection
