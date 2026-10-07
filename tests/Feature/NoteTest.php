<?php

declare(strict_types=1);

use App\Models\Item;
use App\Models\Note;
use App\Models\Project;
use App\Models\User;

it('anggota menambah catatan ke item', function (): void {
    $item = Item::factory()->assigned()->create();

    $this->actingAs(assigneeOf($item))
        ->post("/items/{$item->id}/notes", ['body' => 'Sedang dicek'])
        ->assertRedirect();

    expect($item->notes()->sole())
        ->body->toBe('Sedang dicek')
        ->is_system->toBeFalse();
});

it('admin tidak bisa menambah catatan', function (): void {
    $item = Item::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->post("/items/{$item->id}/notes", ['body' => 'x'])
        ->assertForbidden();
});

it('menambah catatan ke project', function (): void {
    $project = Project::factory()->create();

    $this->actingAs(manager())
        ->post("/projects/{$project->id}/notes", ['body' => 'Kickoff Senin'])
        ->assertRedirect();

    expect($project->notes()->count())->toBe(1);
});

it('penulis bisa mengedit catatan dalam 15 menit', function (): void {
    $item = Item::factory()->create();
    $author = manager();
    $note = $item->notes()->create(['user_id' => $author->id, 'body' => 'awal', 'is_system' => false]);

    $this->travel(10)->minutes();

    $this->actingAs($author)->put("/notes/{$note->id}", ['body' => 'revisi'])->assertRedirect();

    expect($note->fresh()->body)->toBe('revisi');
});

it('catatan terkunci setelah 15 menit', function (): void {
    $item = Item::factory()->create();
    $author = manager();
    $note = $item->notes()->create(['user_id' => $author->id, 'body' => 'awal', 'is_system' => false]);

    $this->travel(16)->minutes();

    $this->actingAs($author)->put("/notes/{$note->id}", ['body' => 'revisi'])->assertForbidden();
    $this->actingAs($author)->delete("/notes/{$note->id}")->assertForbidden();
});

it('user lain tidak bisa mengubah atau menghapus catatan', function (): void {
    $item = Item::factory()->create();
    $note = $item->notes()->create(['user_id' => manager()->id, 'body' => 'awal', 'is_system' => false]);

    $this->actingAs(manager())->delete("/notes/{$note->id}")->assertForbidden();
});

it('catatan sistem tidak bisa diubah', function (): void {
    $item = Item::factory()->create();
    $author = manager();
    $note = $item->notes()->create(['user_id' => $author->id, 'body' => 'sistem', 'is_system' => true]);

    $this->actingAs($author)->put("/notes/{$note->id}", ['body' => 'ubah'])->assertForbidden();
    expect(Note::query()->find($note->id))->not->toBeNull();
});
