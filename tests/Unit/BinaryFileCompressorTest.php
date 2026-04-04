<?php

namespace Tests\Unit;

use App\Services\BinaryFileCompressor;
use PHPUnit\Framework\TestCase;

class BinaryFileCompressorTest extends TestCase
{
    private BinaryFileCompressor $svc;
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc    = new BinaryFileCompressor();
        $this->tmpDir = sys_get_temp_dir() . '/bfc_test_' . uniqid();
        mkdir($this->tmpDir, 0700, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        foreach (glob($this->tmpDir . '/*') as $f) {
            unlink($f);
        }
        rmdir($this->tmpDir);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function roundTrip(string $content): string
    {
        $src = $this->tmpDir . '/input.txt';
        $bin = $this->tmpDir . '/compressed.bin';
        $dst = $this->tmpDir . '/output.txt';

        file_put_contents($src, $content);
        $this->svc->compress($src, $bin);
        $this->svc->decompress($bin, $dst);

        return file_get_contents($dst);
    }

    private function assertRoundTrip(string $content): void
    {
        $this->assertSame($content, $this->roundTrip($content));
    }

    // -------------------------------------------------------------------------
    // canEncode
    // -------------------------------------------------------------------------

    public function test_canEncode_returns_true_for_valid_text_file(): void
    {
        $src = $this->tmpDir . '/ok.txt';
        file_put_contents($src, "3\n10 42 12\n");
        $this->assertTrue($this->svc->canEncode($src));
    }

    public function test_canEncode_returns_false_for_missing_file(): void
    {
        $result = $this->svc->canEncode('/nonexistent/file.txt', $reason);
        $this->assertFalse($result);
        $this->assertStringContainsString('Cannot open file', (string) $reason);
    }

    public function test_canEncode_returns_false_for_binary_file(): void
    {
        $src = $this->tmpDir . '/binary.bin';
        file_put_contents($src, "hello\x00world");
        $result = $this->svc->canEncode($src, $reason);
        $this->assertFalse($result);
        $this->assertStringContainsString('null byte', (string) $reason);
    }

    public function test_canEncode_returns_false_for_invalid_utf8(): void
    {
        $src = $this->tmpDir . '/badencode.txt';
        file_put_contents($src, "hello \xFF\xFE world");
        $result = $this->svc->canEncode($src, $reason);
        $this->assertFalse($result);
        $this->assertStringContainsString('UTF-8', (string) $reason);
    }

    public function test_canEncode_returns_false_for_float_with_too_many_decimal_digits(): void
    {
        $src = $this->tmpDir . '/bigfloat.txt';
        file_put_contents($src, '3.' . str_repeat('1', 256));
        $result = $this->svc->canEncode($src, $reason);
        $this->assertFalse($result);
        $this->assertStringContainsString('255 decimal digits', (string) $reason);
    }

    // -------------------------------------------------------------------------
    // Integers – single values
    // -------------------------------------------------------------------------

    public function test_single_int1_value(): void
    {
        $this->assertRoundTrip('42');
    }

    public function test_single_negative_int1_value(): void
    {
        $this->assertRoundTrip('-1');
    }

    public function test_int1_boundary_values(): void
    {
        $this->assertRoundTrip('-128');
        $this->assertRoundTrip('127');
    }

    public function test_int2_value(): void
    {
        $this->assertRoundTrip('1000');
        $this->assertRoundTrip('-1000');
    }

    public function test_int4_value(): void
    {
        $this->assertRoundTrip('2147483647');
        $this->assertRoundTrip('-2147483648');
    }

    public function test_int8_max_value(): void
    {
        $this->assertRoundTrip((string) PHP_INT_MAX);
    }

    public function test_int8_min_value(): void
    {
        $this->assertRoundTrip((string) PHP_INT_MIN);
    }

    // -------------------------------------------------------------------------
    // Integers – multi-token lines
    // -------------------------------------------------------------------------

    public function test_multiple_ints_on_one_line(): void
    {
        $this->assertRoundTrip('10 42 12');
    }

    public function test_multiple_lines_of_ints(): void
    {
        $this->assertRoundTrip("3\n10 42 12");
    }

    public function test_mixed_positive_negative_ints(): void
    {
        $this->assertRoundTrip('-5 0 5 100 -100');
    }

    // -------------------------------------------------------------------------
    // Vector encoding
    // -------------------------------------------------------------------------

    public function test_vector_of_int1(): void
    {
        // All values fit in 1 byte → VEC_INT1
        $this->assertRoundTrip('1 2 3 4 5');
    }

    public function test_vector_of_int2(): void
    {
        // 1000 requires INT2, so whole line → VEC_INT2
        $this->assertRoundTrip('1000 2000 3000');
    }

    public function test_vector_worst_tag_governs_all(): void
    {
        // Mix of small and large: worst is INT2, so all encoded in 2 bytes
        $this->assertRoundTrip('1 2 1000');
    }

    public function test_large_vector_round_trip(): void
    {
        $values = implode(' ', range(1, 500));
        $this->assertRoundTrip($values);
    }

    public function test_vector_not_used_for_single_token(): void
    {
        // Single integer must still round-trip correctly
        $this->assertRoundTrip('99');
    }

    public function test_vector_with_negative_values(): void
    {
        $this->assertRoundTrip('-3 -2 -1 0 1 2 3');
    }

    // -------------------------------------------------------------------------
    // Floats
    // -------------------------------------------------------------------------

    public function test_simple_float(): void
    {
        $this->assertRoundTrip('3.14');
    }

    public function test_float_leading_zero_in_decimal(): void
    {
        // Critical: 3.09 must not become 3.9
        $this->assertRoundTrip('3.09');
    }

    public function test_float_multiple_leading_zeros_in_decimal(): void
    {
        $this->assertRoundTrip('1.001');
    }

    public function test_negative_float(): void
    {
        $this->assertRoundTrip('-1.5');
    }

    public function test_negative_float_zero_integer_part(): void
    {
        // Critical: sign must be preserved when intPart == 0
        $this->assertRoundTrip('-0.5');
    }

    public function test_float_zero(): void
    {
        $this->assertRoundTrip('0.0');
    }

    public function test_multiple_floats_on_line(): void
    {
        $this->assertRoundTrip('1.5 2.75 -0.25');
    }

    // -------------------------------------------------------------------------
    // Strings
    // -------------------------------------------------------------------------

    public function test_simple_string(): void
    {
        $this->assertRoundTrip('hello');
    }

    public function test_string_with_spaces(): void
    {
        $this->assertRoundTrip('hello world');
    }

    public function test_mixed_string_and_integers(): void
    {
        $this->assertRoundTrip('abc 123 def');
    }

    public function test_string_with_utf8(): void
    {
        $this->assertRoundTrip('olá mundo');
    }

    public function test_long_string_chunked(): void
    {
        // Exceeds uint16 max → must be split into STRING + STRING_CONCAT
        $long = str_repeat('a', 70000);
        $this->assertRoundTrip($long);
    }

    // -------------------------------------------------------------------------
    // Multiline & edge cases
    // -------------------------------------------------------------------------

    public function test_empty_lines_preserved(): void
    {
        $this->assertRoundTrip("first\n\nsecond");
    }

    public function test_multiple_spaces_between_tokens_preserved(): void
    {
        $this->assertRoundTrip('a  b');  // two spaces
    }

    public function test_mixed_types_on_same_line(): void
    {
        $this->assertRoundTrip('foo 42 3.14 bar');
    }

    public function test_file_without_trailing_newline(): void
    {
        $this->assertRoundTrip('no newline at end');
    }

    public function test_file_with_trailing_newline(): void
    {
        $this->assertRoundTrip("line one\nline two\n");
    }

    // -------------------------------------------------------------------------
    // Compressed file validity
    // -------------------------------------------------------------------------

    public function test_compressed_file_starts_with_magic_header(): void
    {
        $src = $this->tmpDir . '/input.txt';
        $bin = $this->tmpDir . '/out.bin';
        file_put_contents($src, '1 2 3');
        $this->svc->compress($src, $bin);

        $header = file_get_contents($bin, false, null, 0, 4);
        $this->assertSame(BinaryFileCompressor::MAGIC, $header);
    }

    public function test_decompress_throws_on_invalid_magic(): void
    {
        $bad = $this->tmpDir . '/bad.bin';
        $dst = $this->tmpDir . '/out.txt';
        file_put_contents($bad, 'XXXX' . chr(0x04) . chr(42));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('magic header');
        $this->svc->decompress($bad, $dst);
    }

    public function test_vector_is_smaller_than_individual_tokens(): void
    {
        // 100 small integers encoded as a vector should be smaller than as individual tokens
        $src    = $this->tmpDir . '/input.txt';
        $binVec = $this->tmpDir . '/vec.bin';

        file_put_contents($src, implode(' ', range(1, 100)));
        $this->svc->compress($src, $binVec);

        // Vector: magic(4) + tag(1) + count(4) + 100×1 = 109 bytes
        // Individual: magic(4) + 100×(tag(1)+value(1)) = 204 bytes
        $this->assertLessThan(204, filesize($binVec));
    }

    // -------------------------------------------------------------------------
    // compressedSize consistency
    // -------------------------------------------------------------------------

    public function test_compressed_size_matches_actual_file_size(): void
    {
        $src = $this->tmpDir . '/input.txt';
        $bin = $this->tmpDir . '/out.bin';
        file_put_contents($src, "3\n10 42 12\n");

        $this->svc->compress($src, $bin);

        $this->assertSame(filesize($bin), $this->svc->compressedSize($src));
    }

    public function test_compressed_size_matches_for_large_int_vector(): void
    {
        $src = $this->tmpDir . '/input.txt';
        $bin = $this->tmpDir . '/out.bin';
        file_put_contents($src, implode(' ', range(1, 1000)));

        $this->svc->compress($src, $bin);

        $this->assertSame(filesize($bin), $this->svc->compressedSize($src));
    }

    public function test_compressed_size_matches_for_floats(): void
    {
        $src = $this->tmpDir . '/input.txt';
        $bin = $this->tmpDir . '/out.bin';
        file_put_contents($src, "3.14 2.71 -0.5 1.001\n0.09 100.00");

        $this->svc->compress($src, $bin);

        $this->assertSame(filesize($bin), $this->svc->compressedSize($src));
    }

    public function test_compressed_size_matches_for_mixed_content(): void
    {
        $src = $this->tmpDir . '/input.txt';
        $bin = $this->tmpDir . '/out.bin';
        file_put_contents($src, "hello world\n42 -7 3.14\nfoo bar baz\n");

        $this->svc->compress($src, $bin);

        $this->assertSame(filesize($bin), $this->svc->compressedSize($src));
    }

    public function test_compressed_size_matches_for_multiline_file(): void
    {
        $src = $this->tmpDir . '/input.txt';
        $bin = $this->tmpDir . '/out.bin';

        $lines = [];
        for ($i = 1; $i <= 100; $i++) {
            $lines[] = implode(' ', range($i, $i + 9));
        }
        file_put_contents($src, implode("\n", $lines));

        $this->svc->compress($src, $bin);

        $this->assertSame(filesize($bin), $this->svc->compressedSize($src));
    }

    // -------------------------------------------------------------------------
    // Larger and more complex round-trips
    // -------------------------------------------------------------------------

    public function test_large_competitive_programming_input(): void
    {
        // Simulates a typical CP input: first line = N, then N lines of K integers
        $n      = 200;
        $k      = 50;
        $lines  = ["{$n}"];
        for ($i = 0; $i < $n; $i++) {
            $row = [];
            for ($j = 0; $j < $k; $j++) {
                $row[] = random_int(-100000, 100000);
            }
            $lines[] = implode(' ', $row);
        }
        $content = implode("\n", $lines);

        $this->assertRoundTrip($content);
    }

    public function test_large_float_matrix(): void
    {
        $rows = [];
        for ($i = 0; $i < 50; $i++) {
            $cols = [];
            for ($j = 0; $j < 20; $j++) {
                $int  = random_int(0, 999);
                $frac = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
                $cols[] = "{$int}.{$frac}";
            }
            $rows[] = implode(' ', $cols);
        }
        $this->assertRoundTrip(implode("\n", $rows));
    }

    public function test_mixed_strings_and_numbers_multi_line(): void
    {
        $content = implode("\n", [
            'Alice 95 3.85',
            'Bob 87 3.20',
            'Charlie 100 4.00',
            'Diana 78 2.95',
            implode(' ', range(1, 30)),
            'end of file',
        ]);
        $this->assertRoundTrip($content);
    }

    public function test_all_int_sizes_in_same_file(): void
    {
        // Exercises INT1 through INT8 in a single file
        $content = implode("\n", [
            '127',                          // INT1 max
            '-128',                         // INT1 min
            '32767',                        // INT2 max
            '-32768',                       // INT2 min
            '8388607',                      // INT3 max
            '2147483647',                   // INT4 max
            '-2147483648',                  // INT4 min
            (string) PHP_INT_MAX,           // INT8 max
            (string) PHP_INT_MIN,           // INT8 min
        ]);
        $this->assertRoundTrip($content);
    }

    public function test_repeated_zeros_vector(): void
    {
        $this->assertRoundTrip(implode(' ', array_fill(0, 500, 0)));
    }

    public function test_large_file_with_trailing_newline(): void
    {
        $lines = [];
        for ($i = 0; $i < 300; $i++) {
            $lines[] = implode(' ', range($i * 10, $i * 10 + 9));
        }
        $this->assertRoundTrip(implode("\n", $lines) . "\n");
    }

    public function test_file_with_only_empty_lines(): void
    {
        $this->assertRoundTrip("\n\n\n");
    }

    public function test_compressed_size_is_less_than_raw_for_int_heavy_file(): void
    {
        $src = $this->tmpDir . '/input.txt';
        file_put_contents($src, implode("\n", array_map(
            fn ($i) => implode(' ', range($i * 100, $i * 100 + 99)),
            range(0, 49)
        )));

        $rawSize = filesize($src);
        $bfcSize = $this->svc->compressedSize($src);

        $this->assertLessThan($rawSize, $bfcSize);
    }
}
