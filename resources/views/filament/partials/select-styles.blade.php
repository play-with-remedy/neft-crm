<style>
    select:not(:disabled):not([aria-disabled="true"]),
    .fi-select-input-btn:not(:disabled):not(.fi-disabled):not([aria-disabled="true"]),
    .fi-select-input-option:not(.fi-disabled):not([aria-disabled="true"]),
    .fi-select-input-value-remove-btn:not(:disabled),
    .fi-select-input-badge-remove:not(:disabled) {
        cursor: pointer;
    }

    select:disabled,
    .fi-select-input-btn:is(:disabled, .fi-disabled, [aria-disabled="true"]) {
        cursor: not-allowed;
    }
</style>
