<?php

arch()->preset()->php()->ignoring('dump');
arch()->preset()->laravel()->ignoring('dump');

it('works', function (): void {
    expect(true)->toBeTrue();
});
