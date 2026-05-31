<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

it('renders the RAG page for authenticated, verified users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/rag')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Rag'));
});

it('redirects guests to login', function () {
    $this->get('/rag')->assertRedirect('/login');
});
