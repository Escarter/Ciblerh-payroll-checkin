{{--
    SchoolOS-style interactive profile photo upload tile + remove button.
    Props / Variables:
      $user        (default: auth()->user())
      $wireModel   (default: 'photo')
      $wireTarget  (default: 'photo,removePhoto')
      $accept      (default: 'image/png,image/jpeg,image/webp')
      $previewUrl  (optional override, e.g. temporaryUrl in modal forms)
      $initials    (optional override)
      $hasPhoto    (optional override)
--}}
@php
    $targetUser = $user ?? auth()->user();
    $wireModel = $wireModel ?? 'photo';
    $wireTarget = $wireTarget ?? ($wireModel . ',removePhoto');
    $accept = $accept ?? 'image/png,image/jpeg,image/webp';
    $initials = $initials ?? ($targetUser?->initials ?: '—');
    $previewUrl = $previewUrl ?? $targetUser?->photoUrl();
    $hasPhoto = $hasPhoto ?? !empty($targetUser?->photo_path);
@endphp

<div class="rf-photo-upload-row d-flex flex-column flex-sm-row align-items-start gap-3 mb-4 pb-3 border-bottom">
    <label class="rf-avatar-upload-tile position-relative d-inline-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden">
        <input type="file" wire:model="{{ $wireModel }}" accept="{{ $accept }}" class="d-none" />

        <span class="rf-avatar-upload-inner position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center overflow-hidden">
            <span class="rf-avatar-upload-initials fw-semibold">{{ $initials }}</span>

            @if ($previewUrl)
                <span
                    class="rf-avatar-upload-img position-absolute top-0 start-0 w-100 h-100"
                    style="background-image: url('{{ $previewUrl }}');"
                ></span>
            @endif
        </span>

        <span wire:loading.remove wire:target="{{ $wireTarget }}" class="rf-avatar-upload-hover position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" class="text-white">
                <path d="M12 9a3.75 3.75 0 1 0 0 7.5A3.75 3.75 0 0 0 12 9Z" />
                <path fill-rule="evenodd" d="M9.344 3.071a49.52 49.52 0 0 1 5.312 0c.967.052 1.83.585 2.332 1.39l.821 1.317c.24.383.645.643 1.11.71.386.054.77.113 1.152.177 1.432.239 2.429 1.493 2.429 2.909V18a3 3 0 0 1-3 3h-15a3 3 0 0 1-3-3V9.574c0-1.416.997-2.67 2.429-2.909.382-.064.766-.123 1.151-.178a1.56 1.56 0 0 0 1.11-.71l.822-1.315a2.942 2.942 0 0 1 2.332-1.39ZM6.75 12.75a5.25 5.25 0 1 1 10.5 0 5.25 5.25 0 0 1-10.5 0Zm12-1.5a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd" />
            </svg>
        </span>

        <span wire:loading.flex wire:target="{{ $wireTarget }}" class="rf-avatar-upload-loading position-absolute top-0 start-0 w-100 h-100 align-items-center justify-content-center" style="display: none;">
            <span class="spinner-border spinner-border-sm text-white" role="status" aria-hidden="true"></span>
        </span>
    </label>

    <div class="flex-grow-1 min-w-0">
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="fw-semibold text-gray-800" style="font-size: .875rem;">{{ __('common.profile_photo') }}</span>
            <span class="badge bg-gray-100 text-gray-600 fw-normal" style="font-size: .7rem;">{{ __('common.optional') }}</span>
        </div>
        <p class="text-muted mb-2" style="font-size: .8rem; line-height: 1.4;">
            {{ __('common.profile_photo_hint') }}
        </p>

        @if ($hasPhoto)
            <button
                type="button"
                wire:click="removePhoto"
                wire:confirm="{{ __('common.confirm_remove_photo') }}"
                class="btn btn-sm btn-outline-gray-400 d-inline-flex align-items-center gap-1"
            >
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                </svg>
                <span>{{ __('common.remove_photo') }}</span>
            </button>
        @endif

        @error($wireModel)
            <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>
