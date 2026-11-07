{{--
    Shared product form.
    Variables: $action, $method ('POST' or 'PUT'), $submitLabel, $product (optional)
--}}

@php
    $inputBase =
        'block w-full rounded-lg border bg-white px-3.5 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:outline-none focus:ring-2';
    $ok = 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500/20';
    $bad = 'border-red-400 focus:border-red-500 focus:ring-red-500/20';
@endphp

<form action="{{ $action }}" method="POST" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    {{-- Name --}}
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" id="name" name="name" value="{{ old('name', $product?->name) }}" required
            placeholder="e.g. Chicken Adobo" class="mt-1.5 {{ $inputBase }} {{ $errors->has('name') ? $bad : $ok }}">
        @error('name')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Price --}}
    <div>
        <label for="price" class="block text-sm font-medium text-gray-700">Price</label>
        <div class="relative mt-1.5">
            <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-gray-500">₱</span>
            <input type="number" id="price" name="price" value="{{ old('price', $product?->price) }}"
                step="0.01" min="0" required placeholder="0.00"
                class="{{ $inputBase }} pl-8 {{ $errors->has('price') ? $bad : $ok }}">
        </div>
        @error('price')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Description --}}
    <div>
        <div class="flex items-baseline justify-between">
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <span class="text-xs text-gray-400">Optional</span>
        </div>
        <textarea id="description" name="description" rows="4" placeholder="What makes this product worth buying?"
            class="mt-1.5 {{ $inputBase }} {{ $errors->has('description') ? $bad : $ok }}">{{ old('description', $product?->description) }}</textarea>
        @error('description')
            <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Actions --}}
    <div class="flex items-center justify-end gap-3 border-t border-gray-100 pt-6">
        <a href="{{ route('products.index') }}"
            class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-600 transition hover:bg-gray-100 hover:text-gray-900">
            Cancel
        </a>
        <button type="submit"
            class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
            {{ $submitLabel }}
        </button>
    </div>
</form>
