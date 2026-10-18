<?php

namespace Tests\Feature;

use App\Filament\Resources\Leads\Pages\ListLeads;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_can_be_created_edited_and_deleted_in_modals(): void
    {
        Livewire::test(ListLeads::class)
            ->callAction('create', data: [
                'received_at' => '2026-10-18',
                'name' => 'Анна',
                'phone' => '29 1234567',
                'telegram' => '@anna',
                'source_channel' => 'Instagram',
                'stage' => 'Позвонила',
            ])
            ->assertHasNoFormErrors();

        $lead = Lead::query()->sole();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'received_at' => '2026-10-18 00:00:00',
            'name' => 'Анна',
            'phone' => '+375291234567',
            'telegram' => '@anna',
            'source_channel' => 'Instagram',
            'stage' => 'Позвонила',
        ]);
        $this->assertSame('18.10.2026', $lead->received_at->format('d.m.Y'));

        Livewire::test(ListLeads::class)
            ->callTableAction('edit', $lead, data: [
                'received_at' => '2026-10-19',
                'name' => 'Анна',
                'phone' => '29 1234567',
                'telegram' => '@anna',
                'source_channel' => 'Instagram',
                'stage' => 'Дошла до игры',
            ])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'received_at' => '2026-10-19 00:00:00',
            'phone' => '+375291234567',
            'stage' => 'Дошла до игры',
        ]);

        Livewire::test(ListLeads::class)
            ->callTableAction('delete', $lead);

        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_lead_requires_a_date_and_rejects_an_invalid_phone(): void
    {
        Livewire::test(ListLeads::class)
            ->callAction('create', data: [
                'received_at' => null,
                'phone' => '12 345',
            ])
            ->assertHasFormErrors([
                'received_at' => 'required',
                'phone' => 'regex',
            ]);

        $this->assertDatabaseCount('leads', 0);
    }
}
