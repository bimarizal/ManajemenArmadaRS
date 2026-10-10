<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_halaman_utama_diarahkan_ke_panel_admin(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }
}
