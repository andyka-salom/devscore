<?php

declare(strict_types=1);

use App\Actions\TestCases\CreateTestCase;
use App\Actions\TestCases\RecordTestCaseResult;
use App\Actions\TestCases\UpdateTestCase;
use App\Actions\Users\DeleteUser;
use App\Enums\TestCaseResult;
use App\Models\Item;
use App\Models\TestCase as ItemTestCase;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

function scenarioData(array $overrides = []): array
{
    return [
        'scenario' => 'Login dengan password salah',
        'steps' => "1. Buka login\n2. Isi password salah",
        'expected_result' => 'Muncul pesan error',
        ...$overrides,
    ];
}

it('mencatat pembuat test case secara otomatis', function (): void {
    $item = Item::factory()->readyForQa()->create();
    $qa = qaOf($item);

    $this->actingAs($qa)
        ->post("/items/{$item->id}/test-cases", scenarioData(['created_by' => manager()->id]))
        ->assertRedirect();

    $testCase = $item->testCases()->sole();

    expect($testCase->created_by)->toBe($qa->id)
        ->and($testCase->result)->toBe(TestCaseResult::Untested)
        ->and($testCase->tested_by)->toBeNull();
});

it('memvalidasi skenario dan expected result', function (): void {
    $item = Item::factory()->readyForQa()->create();

    $this->actingAs(qaOf($item))
        ->post("/items/{$item->id}/test-cases", ['scenario' => '', 'expected_result' => ''])
        ->assertSessionHasErrors(['scenario', 'expected_result']);
});

it('mengizinkan programmer, QA, dan manager yang bisa melihat item membuat test case', function (string $role): void {
    $item = Item::factory()->inProgress()->create();
    $actor = match ($role) {
        'assignee' => assigneeOf($item),
        'qa' => qaOf($item),
        'manager' => manager(),
    };

    $testCase = app(CreateTestCase::class)->handle($item, $actor, scenarioData());

    expect($testCase->created_by)->toBe($actor->id);
})->with(['assignee', 'qa', 'manager']);

it('menolak user di luar project dan admin membuat test case', function (string $role): void {
    $item = Item::factory()->inProgress()->create();

    app(CreateTestCase::class)->handle($item, User::factory()->{$role}()->create(), scenarioData());
})->with(['programmer', 'qa', 'admin'])->throws(AuthorizationException::class);

it('menolak test case baru pada item yang sudah selesai', function (): void {
    $item = Item::factory()->done()->create();

    app(CreateTestCase::class)->handle($item, manager(), scenarioData());
})->throws(AuthorizationException::class);

it('mengisi penguji otomatis sesuai user yang mengisi hasil', function (): void {
    $testCase = ItemTestCase::factory()->for(Item::factory()->readyForQa())->create();
    $qa = qaOf($testCase->item);

    $this->actingAs($qa)
        ->put("/test-cases/{$testCase->id}/result", [
            'result' => 'failed',
            'actual_result' => 'Tidak ada pesan error',
            'tested_by' => manager()->id,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $testCase->refresh();

    expect($testCase->result)->toBe(TestCaseResult::Failed)
        ->and($testCase->actual_result)->toBe('Tidak ada pesan error')
        ->and($testCase->tested_by)->toBe($qa->id)
        ->and($testCase->tested_at)->not->toBeNull()
        ->and($testCase->created_by)->not->toBe($qa->id);
});

it('mengganti penguji bila hasil diisi ulang oleh QA lain anggota project', function (): void {
    $testCase = ItemTestCase::factory()->passed()->for(Item::factory()->readyForQa())->create();
    $otherQa = User::factory()->qa()->create();
    $testCase->item->project->members()->attach($otherQa);

    app(RecordTestCaseResult::class)->handle($testCase, $otherQa, TestCaseResult::Passed, null);

    expect($testCase->refresh()->tested_by)->toBe($otherQa->id)
        ->and($testCase->actual_result)->toBeNull();
});

it('mewajibkan hasil aktual bila test gagal', function (): void {
    $testCase = ItemTestCase::factory()->for(Item::factory()->readyForQa())->create();

    app(RecordTestCaseResult::class)->handle($testCase, qaOf($testCase->item), TestCaseResult::Failed, '  ');
})->throws(ValidationException::class, 'Hasil aktual');

it('menolak hasil "belum dites"', function (): void {
    $testCase = ItemTestCase::factory()->for(Item::factory()->readyForQa())->create();

    $this->actingAs(qaOf($testCase->item))
        ->put("/test-cases/{$testCase->id}/result", ['result' => 'untested'])
        ->assertSessionHasErrors('result');
});

it('hanya QA dan manager yang boleh mengisi hasil', function (): void {
    $testCase = ItemTestCase::factory()->for(Item::factory()->readyForQa())->create();

    app(RecordTestCaseResult::class)->handle($testCase, assigneeOf($testCase->item), TestCaseResult::Passed, null);
})->throws(AuthorizationException::class);

it('menolak QA di luar project mengisi hasil', function (): void {
    $testCase = ItemTestCase::factory()->for(Item::factory()->readyForQa())->create();

    app(RecordTestCaseResult::class)->handle($testCase, User::factory()->qa()->create(), TestCaseResult::Passed, null);
})->throws(AuthorizationException::class);

it('pembuat dan manager boleh mengubah skenario tanpa mengubah pembuat', function (): void {
    $testCase = ItemTestCase::factory()->for(Item::factory()->readyForQa())->create();
    $creatorId = $testCase->created_by;

    app(UpdateTestCase::class)->handle($testCase, manager(), scenarioData(['scenario' => 'Skenario baru']));

    expect($testCase->refresh()->scenario)->toBe('Skenario baru')
        ->and($testCase->created_by)->toBe($creatorId);
});

it('menolak user lain mengubah atau menghapus skenario', function (): void {
    $testCase = ItemTestCase::factory()->for(Item::factory()->readyForQa())->create();
    $qa = qaOf($testCase->item);

    $this->actingAs($qa)->put("/test-cases/{$testCase->id}", scenarioData())->assertForbidden();
    $this->actingAs($qa)->delete("/test-cases/{$testCase->id}")->assertForbidden();
});

it('pembuat boleh menghapus test case', function (): void {
    $testCase = ItemTestCase::factory()->for(Item::factory()->readyForQa())->create();

    $this->actingAs($testCase->creator)
        ->delete("/test-cases/{$testCase->id}")
        ->assertRedirect();

    expect(ItemTestCase::query()->count())->toBe(0);
});

it('menampilkan test case beserta pembuat dan penguji di detail item', function (): void {
    $item = Item::factory()->readyForQa()->create();
    $tested = ItemTestCase::factory()->failed()->for($item)->create();
    ItemTestCase::factory()->for($item)->create();

    $this->actingAs(qaOf($item))
        ->get("/items/{$item->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('test_cases', 2)
            ->where('test_cases.0.id', $tested->id)
            ->where('test_cases.0.result.value', 'failed')
            ->where('test_cases.0.creator.id', $tested->created_by)
            ->where('test_cases.0.tester.id', $tested->tested_by)
            ->where('test_cases.0.can.record_result', true)
            ->where('test_cases.1.tester', null)
            ->where('can.add_test_case', true)
        );
});

it('tidak bisa menghapus user yang pernah membuat atau mengetes test case', function (): void {
    $testCase = ItemTestCase::factory()->passed()->create();

    expect(app(DeleteUser::class)->hasHistory($testCase->tester))->toBeTrue();
});
