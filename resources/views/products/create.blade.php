@component('layouts.app', ['title' => 'Add product'])
    <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">

        <a href="{{ route('products.index') }}"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-500 transition hover:text-gray-900">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
            Products
        </a>

        <h1 class="mt-4 text-2xl font-semibold tracking-tight text-gray-900">Add product</h1>
        <p class="mt-1 text-sm text-gray-500">Fill in the details below to add a new product.</p>

        <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
            @include('products._form', [
                'action' => route('products.store'),
                'method' => 'POST',
                'submitLabel' => 'Save product',
                'product' => null,
            ])
        </div>
    </div>
@endcomponent
