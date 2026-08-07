<?php

it('returns a successful health response', function () {
    $response = $this->getJson('/api/health');

    $response
        ->assertOk()
        ->assertExactJson([
            'status' => 'ok',
        ]);
});
