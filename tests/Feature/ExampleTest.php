<?php

it('redirects guests to the login page from the root route', function () {
    $response = $this->get('/');

    $response->assertRedirect(route('login', absolute: false));
});
