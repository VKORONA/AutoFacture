<?php

use Crater\Models\Estimate;
use Crater\Models\EstimateAttachment;
use Crater\Models\EstimateItem;
use Crater\Models\EstimateLinePhoto;
use Crater\Models\Tax;
use Crater\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\post;
use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    Storage::fake('local');

    $this->user = User::findOrFail(1);
    $this->companyId = $this->user->companies()->firstOrFail()->id;
    $this->withHeaders(['company' => $this->companyId]);
    Sanctum::actingAs($this->user, ['*']);
});

function estimateWithAssetIdentity(string $lineUuid, string $draftToken, array $overrides = []): array
{
    return array_merge(
        Estimate::factory()->raw([
            'estimate_number' => 'EST-PHOTO-'.Str::upper(Str::random(8)),
            'asset_draft_token' => $draftToken,
            'items' => [
                EstimateItem::factory()->raw([
                    'line_uuid' => $lineUuid,
                ]),
            ],
            'taxes' => [Tax::factory()->raw()],
            'annex_title' => 'Détail photographique',
            'annex_notes' => 'Photos contractuelles associées aux prestations.',
            'include_photo_annex' => true,
        ]),
        $overrides,
    );
}

it('normalizes a line photo to stable 4 by 3 variants', function () {
    $lineUuid = (string) Str::uuid();
    $draftToken = (string) Str::uuid();

    post('/api/v1/estimate-assets/photos', [
        'photo' => UploadedFile::fake()->image('chantier-portrait.png', 900, 1600),
        'line_uuid' => $lineUuid,
        'draft_token' => $draftToken,
    ])
        ->assertCreated()
        ->assertJsonPath('data.line_uuid', $lineUuid)
        ->assertJsonPath('data.width', 1600)
        ->assertJsonPath('data.height', 1200);

    $photo = EstimateLinePhoto::firstOrFail();

    Storage::disk('local')->assertExists($photo->image_path);
    Storage::disk('local')->assertExists($photo->preview_path);
    Storage::disk('local')->assertExists($photo->thumbnail_path);

    $imageSize = getimagesizefromstring(Storage::disk('local')->get($photo->image_path));
    $previewSize = getimagesizefromstring(Storage::disk('local')->get($photo->preview_path));
    $thumbnailSize = getimagesizefromstring(Storage::disk('local')->get($photo->thumbnail_path));

    expect($imageSize[0])->toBe(1600)
        ->and($imageSize[1])->toBe(1200)
        ->and($previewSize[0])->toBe(800)
        ->and($previewSize[1])->toBe(600)
        ->and($thumbnailSize[0])->toBe(240)
        ->and($thumbnailSize[1])->toBe(180);
});

it('limits each estimate line to four photos', function () {
    $lineUuid = (string) Str::uuid();
    $draftToken = (string) Str::uuid();

    foreach (range(1, 4) as $index) {
        post('/api/v1/estimate-assets/photos', [
            'photo' => UploadedFile::fake()->image("photo-{$index}.jpg", 800, 600),
            'line_uuid' => $lineUuid,
            'draft_token' => $draftToken,
        ])->assertCreated();
    }

    post('/api/v1/estimate-assets/photos', [
        'photo' => UploadedFile::fake()->image('photo-5.jpg', 800, 600),
        'line_uuid' => $lineUuid,
        'draft_token' => $draftToken,
    ])->assertStatus(422);

    expect(EstimateLinePhoto::count())->toBe(4);
});

it('claims draft photos and annex attachments when the estimate is saved', function () {
    $lineUuid = (string) Str::uuid();
    $draftToken = (string) Str::uuid();

    post('/api/v1/estimate-assets/photos', [
        'photo' => UploadedFile::fake()->image('facade.jpg', 1200, 800),
        'line_uuid' => $lineUuid,
        'draft_token' => $draftToken,
    ])->assertCreated();

    post('/api/v1/estimate-assets/attachments', [
        'attachment' => UploadedFile::fake()->createWithContent('notice-technique.pdf', '%PDF-1.4 test'),
        'draft_token' => $draftToken,
    ])->assertCreated();

    $response = postJson('/api/v1/estimates', estimateWithAssetIdentity($lineUuid, $draftToken))
        ->assertCreated()
        ->assertJsonPath('data.annex_title', 'Détail photographique')
        ->assertJsonCount(1, 'data.attachments')
        ->assertJsonCount(1, 'data.items.0.line_photos');

    $estimateId = $response->json('data.id');

    $this->assertDatabaseHas('estimate_line_photos', [
        'estimate_id' => $estimateId,
        'line_uuid' => $lineUuid,
        'draft_token' => null,
    ]);

    $this->assertDatabaseHas('estimate_attachments', [
        'estimate_id' => $estimateId,
        'original_name' => 'notice-technique.pdf',
        'draft_token' => null,
    ]);
});

it('refuses executable files as estimate annexes', function () {
    post('/api/v1/estimate-assets/attachments', [
        'attachment' => UploadedFile::fake()->create('programme.exe', 10, 'application/x-msdownload'),
        'draft_token' => (string) Str::uuid(),
    ])->assertStatus(422);

    expect(EstimateAttachment::count())->toBe(0);
});

it('removes stored assets when estimates are deleted', function () {
    $estimate = Estimate::factory()->hasItems(1)->create();
    $lineUuid = $estimate->items()->firstOrFail()->line_uuid;

    post('/api/v1/estimate-assets/photos', [
        'photo' => UploadedFile::fake()->image('suppression.jpg', 800, 600),
        'line_uuid' => $lineUuid,
        'draft_token' => (string) Str::uuid(),
        'estimate_id' => $estimate->id,
    ])->assertCreated();

    $photo = EstimateLinePhoto::firstOrFail();

    postJson('/api/v1/estimates/delete', ['ids' => [$estimate->id]])
        ->assertOk();

    Storage::disk('local')->assertMissing($photo->image_path);
    Storage::disk('local')->assertMissing($photo->preview_path);
    Storage::disk('local')->assertMissing($photo->thumbnail_path);
    expect(EstimateLinePhoto::count())->toBe(0);
});
