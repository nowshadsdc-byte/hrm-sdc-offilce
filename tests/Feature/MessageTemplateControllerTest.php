<?php

use App\Models\MessageTemplate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasTable('message_templates')) {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('body');
            $table->timestamps();
        });
    }

    MessageTemplate::query()->delete();
});

it('lists templates ordered by name', function () {
    MessageTemplate::create(['name' => 'Zeta', 'body' => 'Z body']);
    MessageTemplate::create(['name' => 'Alpha', 'body' => 'A body']);

    $response = $this->getJson('/api/message-templates');

    $response->assertOk();
    $response->assertJsonPath('templates.0.name', 'Alpha');
    $response->assertJsonPath('templates.1.name', 'Zeta');
});

it('creates a template', function () {
    $response = $this->postJson('/api/message-templates', [
        'name' => 'Greeting',
        'body' => 'Hello! Thanks for reaching out.',
    ]);

    $response->assertCreated();
    $response->assertJsonPath('template.name', 'Greeting');

    $this->assertDatabaseHas('message_templates', [
        'name' => 'Greeting',
        'body' => 'Hello! Thanks for reaching out.',
    ]);
});

it('requires name and body to create a template', function () {
    $response = $this->postJson('/api/message-templates', []);

    $response->assertUnprocessable()->assertJsonValidationErrors(['name', 'body']);
});

it('deletes a template', function () {
    $template = MessageTemplate::create(['name' => 'Old', 'body' => 'Old body']);

    $response = $this->deleteJson("/api/message-templates/{$template->id}");

    $response->assertOk()->assertJson(['deleted' => true]);
    $this->assertDatabaseMissing('message_templates', ['id' => $template->id]);
});
