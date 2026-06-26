@php
    $prefix = $prefix ?? 'shipping_';
    $showContactFields = $showContactFields ?? false;
@endphp

<fieldset class="grid md:grid-cols-2 gap-4">
    @if ($showContactFields)
        <div class="ds-field">
            <label for="{{ $prefix }}full_name" class="ds-label">Full name</label>
            <input id="{{ $prefix }}full_name" type="text" name="{{ $prefix }}full_name" value="{{ old($prefix.'full_name', auth()->user()?->name) }}" class="ds-input @error($prefix.'full_name') ds-input-error @enderror">
            @error($prefix.'full_name')<p class="ds-error-text">{{ $message }}</p>@enderror
        </div>
        <div class="ds-field">
            <label for="{{ $prefix }}phone" class="ds-label">Phone</label>
            <input id="{{ $prefix }}phone" type="text" name="{{ $prefix }}phone" value="{{ old($prefix.'phone', auth()->user()?->phone) }}" class="ds-input @error($prefix.'phone') ds-input-error @enderror">
            @error($prefix.'phone')<p class="ds-error-text">{{ $message }}</p>@enderror
        </div>
    @endif

    <div class="md:col-span-2 ds-field">
        <label for="{{ $prefix }}line1" class="ds-label">Address line 1</label>
        <input id="{{ $prefix }}line1" type="text" name="{{ $prefix }}line1" value="{{ old($prefix.'line1') }}" class="ds-input @error($prefix.'line1') ds-input-error @enderror">
        @error($prefix.'line1')<p class="ds-error-text">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-2 ds-field">
        <label for="{{ $prefix }}line2" class="ds-label">Address line 2</label>
        <input id="{{ $prefix }}line2" type="text" name="{{ $prefix }}line2" value="{{ old($prefix.'line2') }}" class="ds-input">
    </div>
    <div class="ds-field">
        <label for="{{ $prefix }}city" class="ds-label">City</label>
        <input id="{{ $prefix }}city" type="text" name="{{ $prefix }}city" value="{{ old($prefix.'city') }}" class="ds-input @error($prefix.'city') ds-input-error @enderror">
        @error($prefix.'city')<p class="ds-error-text">{{ $message }}</p>@enderror
    </div>
    <div class="ds-field">
        <label for="{{ $prefix }}state" class="ds-label">State / Province</label>
        <input id="{{ $prefix }}state" type="text" name="{{ $prefix }}state" value="{{ old($prefix.'state') }}" class="ds-input">
    </div>
    <div class="ds-field">
        <label for="{{ $prefix }}postal_code" class="ds-label">Postal code</label>
        <input id="{{ $prefix }}postal_code" type="text" name="{{ $prefix }}postal_code" value="{{ old($prefix.'postal_code') }}" class="ds-input">
    </div>
    <div class="ds-field">
        <label for="{{ $prefix }}country" class="ds-label">Country</label>
        <input id="{{ $prefix }}country" type="text" name="{{ $prefix }}country" value="{{ old($prefix.'country', 'Pakistan') }}" class="ds-input">
    </div>
</fieldset>
