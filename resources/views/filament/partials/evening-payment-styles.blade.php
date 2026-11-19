<style>
    .fi-evening-payment-repeater > table > thead {
        display: none;
    }

    .fi-evening-payment-repeater > table,
    .fi-evening-payment-repeater > table > tbody {
        display: block;
        width: 100%;
    }

    .fi-evening-payment-repeater > table > tbody > tr {
        display: grid;
        grid-template-columns: minmax(125px, 1fr) 90px auto;
        align-items: center;
        gap: 0.5rem;
        width: 100%;
        padding: 10px;
    }

    .fi-evening-payment-repeater > table > tbody > tr > td {
        display: block;
        min-width: 0;
        padding: 0;
    }

    .fi-evening-payment-repeater .fi-fo-table-repeater-actions {
        flex-wrap: nowrap;
        gap: 0.25rem;
    }

    .fi-evening-participants-repeater > table > tbody > tr {
        padding-block: 0.375rem;
    }

    @media (max-width: 640px) {
        .fi-evening-payment-repeater > table > tbody > tr {
            grid-template-columns: minmax(0, 1fr);
            gap: 0.5rem;
            padding: 8px;
        }

        .fi-evening-payment-repeater > table > tbody > tr > td:last-child,
        .fi-evening-payment-repeater > table > tbody > tr > td:last-child .fi-fo-table-repeater-actions {
            justify-content: flex-end;
        }
    }
</style>
