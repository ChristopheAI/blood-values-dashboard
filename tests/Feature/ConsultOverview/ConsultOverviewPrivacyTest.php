<?php

use App\Models\User;

it('does not carry consult questions in generated get urls', function () {
    $user = User::factory()->create();
    $secretQuestion = 'Could we discuss the training context privately?';

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'from' => '2026-06-01',
            'to' => '2026-06-01',
            'include_context' => '1',
            'questions' => $secretQuestion,
        ])
        ->assertOk()
        ->assertSee($secretQuestion)
        ->assertSee('method="POST"', false)
        ->assertSee('action="'.route('consult-overview.index').'"', false)
        ->assertSee('action="'.route('consult-overview.csv').'"', false)
        ->assertDontSee('/consult-overview?questions=', false)
        ->assertDontSee('/consult-overview.csv?questions=', false)
        ->assertDontSee('questions='.rawurlencode($secretQuestion), false)
        ->assertDontSee('questions='.urlencode($secretQuestion), false);

    $this->actingAs($user)
        ->get(route('consult-overview.index', ['questions' => $secretQuestion]))
        ->assertOk()
        ->assertDontSee($secretQuestion);
});

it('does not carry consult questions into the csv export form', function () {
    $user = User::factory()->create();
    $secretQuestion = 'Could we discuss the training context privately?';

    $this->actingAs($user)
        ->post(route('consult-overview.index'), [
            'include_context' => '1',
            'questions' => $secretQuestion,
        ])
        ->assertOk()
        ->assertSee($secretQuestion)
        ->assertSee('data-test="export-consult-csv-form"', false)
        ->assertDontSee('type="hidden" name="questions"', false)
        ->assertDontSee('value="'.$secretQuestion.'"', false);
});
