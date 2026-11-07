@component('layouts.app', ['title' => 'Products'])
    <div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

        {{-- Page header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900">Products</h1>
                <p class="mt-1 text-sm text-gray-500">Manage the items you sell.</p>
            </div>

            <a href="{{ route('products.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add product
            </a>
        </div>

        {{-- Success message --}}
        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-transition
                class="mt-6 flex items-start justify-between gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                role="status">
                <span>{{ session('success') }}</span>
                <button type="button" @click="show = false" class="text-green-700 hover:text-green-900"
                    aria-label="Dismiss">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        @endif

        {{-- Product list --}}
        <div x-data="{ open: false, action: '', name: '' }" class="mt-6">

            @if ($products->isNotEmpty())
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <ul class="divide-y divide-gray-100">
                        @foreach ($products as $product)
                            <li class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-4 sm:justify-start">
                                        <h2 class="truncate text-base font-medium text-gray-900">{{ $product->name }}</h2>
                                        <span class="shrink-0 text-sm font-semibold text-indigo-600">
                                            ₱{{ number_format($product->price, 2) }}
                                        </span>
                                    </div>

                                    @if ($product->description)
                                        <p class="mt-1 line-clamp-2 text-sm text-gray-500">{{ $product->description }}</p>
                                    @else
                                        <p class="mt-1 text-sm italic text-gray-400">No description</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    <a href="{{ route('products.edit', $product) }}"
                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">
                                        Edit
                                    </a>

                                    <button type="button"
                                        @click="open = true; action = '{{ route('products.destroy', $product) }}'; name = @js($product->name)"
                                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-red-600 transition hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">
                                        Delete
                                    </button>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                {{-- Empty state --}}
                <div class="rounded-xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                    <div
                        class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                        </svg>
                    </div>
                    <h2 class="mt-4 text-base font-medium text-gray-900">No products yet</h2>
                    <p class="mt-1 text-sm text-gray-500">Add your first product to get started.</p>
                    <a href="{{ route('products.create') }}"
                        class="mt-6 inline-flex rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700">
                        Add product
                    </a>
                </div>
            @endif

            {{-- Delete confirmation modal --}}
            <div x-show="open" x-cloak x-transition.opacity
                class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4"
                @keydown.escape.window="open = false" @click.self="open = false" role="dialog" aria-modal="true">
                <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                    <h3 class="text-lg font-semibold text-gray-900">Delete product?</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        <span class="font-medium text-gray-900" x-text="name"></span>
                        will be permanently removed. This can't be undone.
                    </p>

                    <form :action="action" method="POST" class="mt-6 flex justify-end gap-3">
                        @csrf
                        @method('DELETE')

                        <button type="button" @click="open = false"
                            class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2">
                            Delete product
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcomponent
