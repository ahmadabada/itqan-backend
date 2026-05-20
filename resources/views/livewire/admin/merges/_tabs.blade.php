{{-- Shared tabs strip for the Merge module.
     Included by merges/index and merges/history so both routes share one
     interface; wire:navigate keeps it SPA-smooth and the active tab is
     derived from the current route name. --}}
@php
    $currentRoute = request()->route()?->getName();
@endphp
<div class="mb-5 sm:mb-6 border-b border-neutral-200">
    <nav class="flex items-center gap-1 -mb-px" aria-label="تبويبات الدمج">
        <a
            href="{{ route('admin.merges') }}"
            wire:navigate
            class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
                   {{ $currentRoute === 'admin.merges'
                       ? 'border-primary-500 text-primary-700'
                       : 'border-transparent text-neutral-500 hover:text-neutral-800 hover:border-neutral-300' }}"
        >
            دمج الطلاب
        </a>
        <a
            href="{{ route('admin.merges.history') }}"
            wire:navigate
            class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors
                   {{ str_starts_with($currentRoute ?? '', 'admin.merges.history') || $currentRoute === 'admin.merges.show'
                       ? 'border-primary-500 text-primary-700'
                       : 'border-transparent text-neutral-500 hover:text-neutral-800 hover:border-neutral-300' }}"
        >
            سجل عمليات الدمج
        </a>
    </nav>
</div>
