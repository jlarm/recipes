<?php

test('the home page redirects to the recipe index', function () {
    $this->get(route('home'))->assertRedirect(route('recipes.index'));
});
