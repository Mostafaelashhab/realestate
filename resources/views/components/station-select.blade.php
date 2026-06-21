@props([
    'name',
    'placeholder',
    'icon' => 'dot',
    'iconClass' => 'text-rail-600',
    'round' => '',
])

{{-- منتقي محطة: زر يفتح قائمة منسدلة فيها بحث (combobox). القيمة المختارة في input مخفي. --}}
<div class="relative" data-station-select data-name="{{ $name }}">
    <input type="hidden" name="{{ $name }}" id="{{ $name }}">

    <button type="button" data-trigger
        class="relative w-full flex items-center ps-10 pe-14 py-3.5 text-start {{ $round }} focus:outline-none focus:bg-rail-50/40 transition">
        <x-icon :name="$icon" class="absolute start-4 top-1/2 -translate-y-1/2 {{ $icon === 'dot' ? 'w-3 h-3' : 'w-4 h-4' }} {{ $iconClass }}"/>
        <span data-label class="min-w-0 truncate text-slate-400">{{ $placeholder }}</span>
    </button>

    <div data-panel hidden
        class="absolute inset-x-0 top-full mt-2 z-40 bg-white rounded-2xl shadow-xl ring-1 ring-slate-200 overflow-hidden">
        <div class="p-2 border-b border-slate-100">
            <div class="relative">
                <x-icon name="search" class="absolute start-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"/>
                <input data-search type="text" autocomplete="off" placeholder="ابحث عن محطة…"
                    class="w-full bg-slate-50 rounded-xl ps-9 pe-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-rail-500/30">
            </div>
        </div>
        <ul data-list class="max-h-64 overflow-y-auto py-1"></ul>
        <div data-empty hidden class="px-4 py-6 text-center text-sm text-slate-400">مفيش محطة بالاسم ده</div>
    </div>
</div>
