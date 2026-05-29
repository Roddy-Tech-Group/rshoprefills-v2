<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslateEnginePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_translate_engine_is_present_on_storefront_pages(): void
    {
        $this->withoutVite();

        $this->get(route('shop.mobile-app'))
            ->assertOk()
            ->assertSee('google_translate_element', false)
            ->assertSee('googleTranslateElementInit', false)
            ->assertSee('language-changed', false);
    }

    public function test_the_language_map_covers_the_modal_languages(): void
    {
        $this->withoutVite();

        $this->get(route('shop.mobile-app'))
            ->assertOk()
            // A sampling of the 88 offered languages must be mapped to engine codes.
            ->assertSee("'Mandarin Chinese': 'zh-CN'", false)
            ->assertSee("'Spanish': 'es'", false)
            ->assertSee("'Yoruba': 'yo'", false);
    }
}
