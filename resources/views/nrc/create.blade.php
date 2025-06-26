@extends('layouts.app')
@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-blue-600 py-4 px-6">
                <h1 class="text-xl font-bold text-white mm-font">မြန်မာနိုင်ငံသားစိစစ်ရေးကတ်ပြား</h1>
                <p class="text-blue-100 text-sm">Myanmar NRC Form</p>
            </div>

            <form method="POST" action="{{ route('nrc.store') }}" class="p-6">
                @csrf

                @if (session('success'))
                    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="state_code">
                        တိုင်း/ပြည်နယ် (State/Division)
                    </label>
                    <select name="state_code" id="state_code"
                        class="w-full px-3 py-2 border rounded shadow appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                        <option value="">Select State</option>
                        @foreach ($states as $code => $name)
                            <option value="{{ $code }}" {{ old('state_code') == $code ? 'selected' : '' }}>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @error('state_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="township_code">
                        မြို့နယ် (Township)
                    </label>
                    <select name="township_code" id="township_code"
                        class="w-full px-3 py-2 border rounded shadow appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100"
                        disabled required>
                        <option value="">Select State First</option>
                    </select>
                    @error('township_code')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">
                        အမျိုးအစား (Type)
                    </label>
                    <div class="flex flex-wrap gap-4">
                        @foreach ($types as $code => $type)
                            <label class="inline-flex items-center">
                                <input type="radio" name="type" value="{{ $code }}"
                                    class="form-radio h-4 w-4 text-blue-600 focus:ring-blue-500" disabled
                                    {{ old('type') == $code ? 'checked' : '' }} required>
                                <span class="ml-2 mm-font">{{ $type['mm'] }} ({{ $code }})</span>
                            </label>
                        @endforeach
                    </div>
                    @error('type')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2" for="number">
                        နံပါတ် (Number)
                    </label>
                    <input type="text" name="number" id="number"
                        class="w-full px-3 py-2 border rounded shadow appearance-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="123456" value="{{ old('number') }}" required>
                    @error('number')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-600 mb-1">NRC Format Preview:</p>
                    <p id="nrc-preview" class="font-mono text-lg mm-font">-/-(-)-</p>
                </div>

                <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Submit
                </button>
            </form>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .mm-font {
            font-family: 'Padauk', 'Noto Sans Myanmar', sans-serif;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const stateSelect = document.getElementById('state_code');
            const townshipSelect = document.getElementById('township_code');
            const typeRadios = document.querySelectorAll('input[name="type"]');
            const numberInput = document.getElementById('number');
            const nrcPreview = document.getElementById('nrc-preview');
            stateSelect.addEventListener('change', handleStateChange);
            townshipSelect.addEventListener('change', handleTownshipChange);
            typeRadios.forEach(radio => radio.addEventListener('change', updatePreview));
            numberInput.addEventListener('input', updatePreview);
            if (stateSelect.value) {
                handleStateChange();
            }

            async function handleStateChange() {
                const stateCode = stateSelect.value;
                if (!stateCode) {
                    resetTownshipSelect();
                    resetTypeRadios();
                    updatePreview();
                    return;
                }

                try {
                    const townships = await loadTownships(stateCode);
                    const oldTownship = "{{ old('township_code') }}";
                    if (oldTownship && townships[oldTownship]) {
                        townshipSelect.value = oldTownship;
                        enableTypeRadios();
                    }
                } catch (error) {
                    console.error('Township loading failed:', error);
                    showTownshipError();
                } finally {
                    updatePreview();
                }
            }

            function handleTownshipChange() {
                townshipSelect.value ? enableTypeRadios() : resetTypeRadios();
                updatePreview();
            }

            async function loadTownships(stateCode) {
                const townshipSelect = document.getElementById('township_code');

                townshipSelect.disabled = true;
                townshipSelect.innerHTML = '<option value="">Loading...</option>';

                try {
                    const response = await fetch(`/api/nrc/townships/${stateCode}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content || ''
                        }
                    });

                    if (!response.ok) {
                        const errorData = await response.json().catch(() => ({}));
                        throw new Error(errorData.error || `HTTP Error ${response.status}`);
                    }
                    const data = await response.json();

                    if (!data || typeof data !== 'object') {
                        throw new Error('Invalid data format');
                    }
                    townshipSelect.innerHTML = '<option value="">Select Township</option>';
                    Object.entries(data).forEach(([code, name]) => {
                        const newOption = document.createElement('option');
                        newOption.value = code;
                        newOption.text = name;
                        townshipSelect.appendChild(newOption);
                    });

                } catch (error) {
                    console.error('Township load failed:', error);
                    townshipSelect.innerHTML = `
            <option value="">
                Error: ${error.message.replace('Error: ', '')}
            </option>
        `;
                } finally {
                    townshipSelect.disabled = false;
                    updatePreview();
                }
            }

            function showTownshipError() {
                townshipSelect.innerHTML = `
            <option value="">
                Error loading townships. 
                ${stateSelect.value ? 'Try again or check console' : 'Select state first'}
            </option>
        `;
                townshipSelect.disabled = false;
            }

            function resetTownshipSelect() {
                townshipSelect.innerHTML = '<option value="">Select State First</option>';
                townshipSelect.disabled = true;
                townshipSelect.classList.add('bg-gray-100');
            }

            function enableTypeRadios() {
                typeRadios.forEach(radio => radio.disabled = false);
            }

            function resetTypeRadios() {
                typeRadios.forEach(radio => {
                    radio.disabled = true;
                    radio.checked = false;
                });
            }

            function updatePreview() {
                const state = stateSelect.selectedOptions[0].value;
                const township = townshipSelect.selectedOptions[0]?.text.match(/\(([^)]+)\)/)?.[1] || '-';
                const type = document.querySelector('input[name="type"]:checked')?.value || '-';
                const number = numberInput.value || '-';
                console.log(state);
                nrcPreview.textContent = `${state}/${township}(${type})${number}`;
            }
        });
    </script>
@endpush
