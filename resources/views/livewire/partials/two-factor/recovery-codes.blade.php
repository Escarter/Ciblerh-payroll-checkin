@php
    $codes = $codes ?? [];
    $finishAction = $finishAction ?? 'dismissRecoveryCodes';
@endphp

@if(!empty($codes))
    <div
        x-data="{
            copied: false,
            codesText: @js(implode(PHP_EOL, $codes)),
            copyAll() {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(this.codesText);
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2500);
                }
            },
            downloadTxt() {
                const blob = new Blob([this.codesText + '\n'], { type: 'text/plain;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'recovery-codes.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }
        }"
        class="card p-3 mb-3"
        style="background-color: #fffbeb; border: 1px solid #fcd34d !important;"
    >
        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
            <div>
                <h6 class="fw-bold text-dark mb-1">
                    {{ __('two_factor.recovery_codes_heading') }}
                </h6>
                <p class="small text-muted mb-0">
                    {{ __('two_factor.recovery_codes_explanation') }}
                </p>
            </div>
            <span class="badge bg-warning text-dark">
                {{ __('two_factor.recovery_codes_shown_once') }}
            </span>
        </div>

        <div class="row g-2 bg-white border rounded-3 p-3 my-2">
            @foreach($codes as $recoveryCode)
                <div class="col-6 col-sm-4">
                    <div class="p-1 text-center bg-light rounded border font-monospace fw-bold small user-select-all">
                        {{ $recoveryCode }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
            <div class="d-flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="copyAll()"
                    class="btn btn-sm btn-outline-secondary bg-white"
                >
                    <span x-show="!copied">{{ __('two_factor.copy_codes') }}</span>
                    <span x-show="copied" x-cloak class="text-success fw-semibold">{{ __('two_factor.copied') }}</span>
                </button>

                <button
                    type="button"
                    @click="downloadTxt()"
                    class="btn btn-sm btn-outline-secondary bg-white"
                >
                    {{ __('two_factor.download_txt') }}
                </button>
            </div>

            <button
                type="button"
                wire:click="{{ $finishAction }}"
                class="btn btn-sm btn-primary"
            >
                {{ __('two_factor.i_have_saved_these_codes') }}
            </button>
        </div>
    </div>
@endif
