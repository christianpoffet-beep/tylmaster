<?php

namespace Tests\Feature\Smoke;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\ContractParty;
use App\Models\Document;
use App\Models\MusicSubmission;
use App\Models\Organization;
use App\Models\Task;
use App\Models\Track;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A contract stays on record once it is active, expired or terminated. Only
 * a draft can be deleted, and it takes its parties, links and documents with
 * it, while tasks and submissions survive without the link.
 */
class ContractDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function contract(string $status): Contract
    {
        $contract = Contract::create(['title' => 'Labeldeal ' . $status, 'type' => 'label', 'status' => $status, 'language' => 'de']);

        $org = Organization::create(['type' => 'label', 'names' => ['The yelling light']]);
        $contact = Contact::create(['first_name' => 'Max', 'last_name' => 'Muster']);
        ContractParty::create(['contract_id' => $contract->id, 'organization_id' => $org->id, 'share' => 50, 'sort_order' => 0]);
        ContractParty::create(['contract_id' => $contract->id, 'contact_id' => $contact->id, 'share' => 50, 'sort_order' => 1]);
        $contract->organizations()->attach($org->id);
        $contract->tracks()->attach(Track::create(['title' => 'Nachtblau', 'status' => 'draft'])->id);

        return $contract;
    }

    public function test_a_draft_is_deleted_with_its_parties_links_and_documents(): void
    {
        Storage::fake('documents');

        $contract = $this->contract('draft');
        Storage::disk('documents')->put('contracts/archived/entwurf.pdf', 'pdf');
        $document = $contract->documents()->create([
            'title' => 'entwurf.pdf',
            'category' => 'contract',
            'file_path' => 'contracts/archived/entwurf.pdf',
            'mime_type' => 'application/pdf',
            'is_archived' => true,
        ]);
        $task = Task::create(['title' => 'Vertrag nachfassen']);
        $contract->tasks()->attach($task->id);
        $submission = MusicSubmission::create(['artist_name' => 'Max Muster', 'contract_id' => $contract->id]);

        $this->actingAs(User::factory()->create())
            ->delete("/admin/contracts/{$contract->id}")
            ->assertRedirect(route('admin.contracts.index'))
            ->assertSessionHas('success');

        $this->assertNull(Contract::find($contract->id));
        $this->assertSame(0, ContractParty::where('contract_id', $contract->id)->count());
        $this->assertSame(0, DB::table('contract_track')->where('contract_id', $contract->id)->count());
        $this->assertSame(0, DB::table('contract_organization')->where('contract_id', $contract->id)->count());

        $this->assertNull(Document::withTrashed()->find($document->id), 'No document may point at the deleted contract.');
        $this->assertFalse(Storage::disk('documents')->exists('contracts/archived/entwurf.pdf'));

        $this->assertNotNull($task->fresh(), 'The task stays, only the link goes.');
        $this->assertSame(0, DB::table('taskables')->where('taskable_type', Contract::class)->where('taskable_id', $contract->id)->count());
        $this->assertNull($submission->fresh()->contract_id);
    }

    public function test_a_contract_beyond_the_draft_cannot_be_deleted(): void
    {
        foreach (['active', 'expired', 'terminated'] as $status) {
            $contract = $this->contract($status);

            $this->actingAs(User::factory()->create())
                ->delete("/admin/contracts/{$contract->id}")
                ->assertRedirect(route('admin.contracts.show', $contract))
                ->assertSessionHas('error');

            $this->assertNotNull($contract->fresh(), "A contract with status {$status} must stay.");
            $this->assertSame(2, $contract->parties()->count());
        }
    }

    public function test_the_edit_page_offers_the_button_only_for_a_draft(): void
    {
        $user = User::factory()->create();

        $draft = $this->contract('draft');
        $this->actingAs($user)->get("/admin/contracts/{$draft->id}/edit")
            ->assertOk()
            ->assertSee('Entwurf löschen')
            ->assertSee(route('admin.contracts.destroy', $draft), false);

        $active = $this->contract('active');
        $this->actingAs($user)->get("/admin/contracts/{$active->id}/edit")
            ->assertOk()
            ->assertDontSee('Entwurf löschen')
            ->assertSee('Löschen geht nur im Status «Entwurf»');
    }
}
