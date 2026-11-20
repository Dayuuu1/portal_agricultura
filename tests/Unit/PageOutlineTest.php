<?php

namespace Tests\Unit;

use App\Support\Pages\PageOutline;
use Tests\TestCase;

class PageOutlineTest extends TestCase
{
    public function test_section_titles_get_unique_anchors_and_feed_the_index(): void
    {
        $outline = PageOutline::fromHtml('<p>Intro</p><h2 id="viejo">Principios</h2><p>…</p><h3>Respeto <strong>mutuo</strong></h3><h2>Deberes</h2><h2>Deberes</h2>');

        $this->assertSame([
            ['id' => 'principios', 'text' => 'Principios', 'level' => 2],
            ['id' => 'respeto-mutuo', 'text' => 'Respeto mutuo', 'level' => 3],
            ['id' => 'deberes', 'text' => 'Deberes', 'level' => 2],
            ['id' => 'deberes-2', 'text' => 'Deberes', 'level' => 2],
        ], $outline->headings);

        $this->assertStringContainsString('<h2 id="principios">Principios</h2>', $outline->html);
        $this->assertStringContainsString('<h3 id="respeto-mutuo">Respeto <strong>mutuo</strong></h3>', $outline->html);
        $this->assertTrue($outline->hasIndex());
    }

    public function test_a_single_section_does_not_need_an_index(): void
    {
        $outline = PageOutline::fromHtml('<p>Los servidores se rigen por la Ley N° 27815.</p><h2>Principios</h2><ul><li>Respeto</li></ul>');

        $this->assertFalse($outline->hasIndex());
        $this->assertFalse(PageOutline::fromHtml(null)->hasIndex());
    }
}
