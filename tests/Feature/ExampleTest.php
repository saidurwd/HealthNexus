<?php

use Illuminate\Support\Facades\Route;

test('the application returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(302);
});

test('login page returns a successful response', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});
