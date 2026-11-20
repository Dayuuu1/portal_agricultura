<?php

namespace Tests\Unit;

use App\Support\WordPress\MysqlDumpReader;
use PHPUnit\Framework\TestCase;

class MysqlDumpReaderTest extends TestCase
{
    private function reader(): MysqlDumpReader
    {
        return new MysqlDumpReader(dirname(__DIR__).'/Fixtures/wordpress-dump.sql');
    }

    public function test_it_detects_the_table_prefix(): void
    {
        $this->assertSame('wp_', $this->reader()->detectPrefix());
    }

    public function test_it_maps_values_to_column_names_and_unescapes_strings(): void
    {
        $rows = iterator_to_array($this->reader()->rows('wp_posts'), false);
        $news = $rows[0];

        $this->assertCount(13, $rows);
        $this->assertSame('10', $news['ID']);
        $this->assertSame('goremad-entrego-semillas', $news['post_name']);
        $this->assertStringContainsString("parcelas.\n\nLa entrega", $news['post_content']);
        $this->assertStringContainsString('"Agencia Agraria"', $news['post_content']);
    }

    public function test_it_reads_binary_values_and_nulls(): void
    {
        $rows = iterator_to_array($this->reader()->rows('wp_ddownload_statistics'), false);

        $this->assertCount(4, $rows);
        $this->assertSame("\0\0\0", $rows[0]['user_ip']);
        $this->assertSame("Mozilla/5.0 (Windows; 'test')", $rows[0]['user_agent']);
        $this->assertSame('failed', $rows[3]['status']);
    }
}
