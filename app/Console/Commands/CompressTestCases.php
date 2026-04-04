<?php

namespace App\Console\Commands;

use App\Models\File;
use App\Models\TestCase;
use App\Services\BinaryFileCompressor;
use Illuminate\Console\Command;
use Illuminate\Support\Benchmark;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Output\ConsoleSectionOutput;

class CompressTestCases extends Command
{
    protected $signature = 'app:compress-testcases
                            {--compress : Actually compress all eligible test cases}
                            {--force   : Re-compress already-compressed test cases}';

    protected $description = 'Analyse or compress BFC-eligible test case files';

    private BinaryFileCompressor $bfc;

    // -------------------------------------------------------------------------

    public function handle(): int
    {
        $this->bfc = new BinaryFileCompressor();

        if ($this->option('compress')) {
            return $this->runCompress();
        }

        return $this->runReport();
    }

    // -------------------------------------------------------------------------
    // REPORT MODE (default)
    // -------------------------------------------------------------------------

    private function runReport(): int
    {
        $total = TestCase::count();

        if ($total === 0) {
            $this->info('No test cases found.');
            return self::SUCCESS;
        }

        // Single section for both the progress bar line and the live stats table.
        // Using one section avoids the cursor-sync issues that arise when two
        // sections update independently.
        /** @var \Symfony\Component\Console\Output\ConsoleOutput $consoleOutput */
        $consoleOutput = $this->output->getOutput();
        /** @var ConsoleSectionOutput $section */
        $section = $consoleOutput->section();

        $stats = [
            'total'               => 0,
            'in_compressed'       => 0,
            'out_compressed'      => 0,
            'both_compressed'     => 0,
            'in_compressed_size'  => 0,
            'out_compressed_size' => 0,
            'in_raw_size'         => 0,
            'out_raw_size'        => 0,
            'in_compressible'     => 0,
            'out_compressible'    => 0,
            'in_saving'           => 0,
            'out_saving'          => 0,
            'in_not_eligible'     => 0,
            'out_not_eligible'    => 0,
            'in_db_stored'        => 0,
            'out_db_stored'       => 0,

            // timing accumulators (milliseconds)
            'ms_read'             => 0.0,   // writeTempFile
            'ms_can_encode'       => 0.0,   // canEncode check
            'ms_compress'         => 0.0,   // BFC compress
            'calls_read'          => 0,
            'calls_can_encode'    => 0,
            'calls_compress'      => 0,
        ];

        TestCase::with(['inputfile', 'outputfile'])->chunk(50, function ($cases) use (&$stats, $total, $section) {
            foreach ($cases as $tc) {
                $stats['total']++;

                $this->analyseFile(
                    file:            $tc->inputfile,
                    isCompressed:    $tc->is_input_compressed,
                    compressedSize:  $stats['in_compressed_size'],
                    compressedCount: $stats['in_compressed'],
                    rawSize:         $stats['in_raw_size'],
                    compressible:    $stats['in_compressible'],
                    saving:          $stats['in_saving'],
                    notEligible:     $stats['in_not_eligible'],
                    dbStored:        $stats['in_db_stored'],
                    msRead:          $stats['ms_read'],
                    msCanEncode:     $stats['ms_can_encode'],
                    msCompress:      $stats['ms_compress'],
                    callsRead:       $stats['calls_read'],
                    callsCanEncode:  $stats['calls_can_encode'],
                    callsCompress:   $stats['calls_compress'],
                );

                $this->analyseFile(
                    file:            $tc->outputfile,
                    isCompressed:    $tc->is_output_compressed,
                    compressedSize:  $stats['out_compressed_size'],
                    compressedCount: $stats['out_compressed'],
                    rawSize:         $stats['out_raw_size'],
                    compressible:    $stats['out_compressible'],
                    saving:          $stats['out_saving'],
                    notEligible:     $stats['out_not_eligible'],
                    dbStored:        $stats['out_db_stored'],
                    msRead:          $stats['ms_read'],
                    msCanEncode:     $stats['ms_can_encode'],
                    msCompress:      $stats['ms_compress'],
                    callsRead:       $stats['calls_read'],
                    callsCanEncode:  $stats['calls_can_encode'],
                    callsCompress:   $stats['calls_compress'],
                );

                if ($tc->is_input_compressed && $tc->is_output_compressed) {
                    $stats['both_compressed']++;
                }

                $this->renderLiveTable($stats, $total, $section);
            }
        });

        $section->clear();
        $this->renderReport($stats);
        return self::SUCCESS;
    }

    /**
     * Clear the single section and reprint the progress bar + stats table in place.
     */
    private function renderLiveTable(array $s, int $total, ConsoleSectionOutput $section): void
    {
        $totalIn  = $s['in_compressed']  + $s['in_compressible']  + $s['in_not_eligible'];
        $totalOut = $s['out_compressed'] + $s['out_compressible'] + $s['out_not_eligible'];
        $totalRaw = $s['in_raw_size'] + $s['out_raw_size'];
        $saving   = $s['in_saving']   + $s['out_saving'];
        $pct      = $totalRaw > 0 ? round($saving / $totalRaw * 100, 1) : 0;

        $avgRead      = $s['calls_read']       > 0 ? $s['ms_read']       / $s['calls_read']       : 0;
        $avgCanEncode = $s['calls_can_encode']  > 0 ? $s['ms_can_encode'] / $s['calls_can_encode'] : 0;
        $avgCompress  = $s['calls_compress']    > 0 ? $s['ms_compress']   / $s['calls_compress']   : 0;

        // Progress bar rendered manually so it lives in the same section
        $done    = $s['total'];
        $barWidth = 40;
        $filled  = $total > 0 ? (int) round($barWidth * $done / $total) : 0;
        $bar     = str_repeat('=', max(0, $filled - 1)) . ($filled > 0 ? '>' : '') . str_repeat('-', $barWidth - $filled);
        $pctBar  = $total > 0 ? round($done / $total * 100) : 0;
        $barLine = " [{$bar}] {$done}/{$total} ({$pctBar}%)";

        $rows = [
            [$barLine, ''],   // progress bar row — full width, no padding needed
            ['', ''],
            ['<fg=cyan>Processed</>',                      $done],
            ['<fg=cyan>Both compressed</>',                $s['both_compressed']],
            ['', ''],
            ['<fg=yellow>Inputs — already compressed</>',  "{$s['in_compressed']} / {$totalIn}"],
            ['<fg=yellow>Inputs — raw storage</>',         $this->fmtBytes($s['in_raw_size'])],
            ['<fg=yellow>Inputs — compressible</>',        $s['in_compressible']],
            ['<fg=yellow>Inputs — potential saving</>',    $this->fmtBytes($s['in_saving'])],
            ['', ''],
            ['<fg=yellow>Outputs — already compressed</>', "{$s['out_compressed']} / {$totalOut}"],
            ['<fg=yellow>Outputs — raw storage</>',        $this->fmtBytes($s['out_raw_size'])],
            ['<fg=yellow>Outputs — compressible</>',       $s['out_compressible']],
            ['<fg=yellow>Outputs — potential saving</>',   $this->fmtBytes($s['out_saving'])],
            ['', ''],
            ['<fg=green>Total potential saving</>',        $this->fmtBytes($saving) . " ({$pct}%)"],
            ['', ''],
            ['<fg=gray>Read file  (total / avg)</>',  $this->fmtMs($s['ms_read'])       . ' / ' . $this->fmtMs($avgRead)],
            ['<fg=gray>canEncode  (total / avg)</>',  $this->fmtMs($s['ms_can_encode'])  . ' / ' . $this->fmtMs($avgCanEncode)],
            ['<fg=gray>Compress   (total / avg)</>',  $this->fmtMs($s['ms_compress'])    . ' / ' . $this->fmtMs($avgCompress)],
        ];

        $section->clear();
        foreach ($rows as [$label, $value]) {
            if ($label === '') {
                $section->writeln('');
                continue;
            }
            // First row (progress bar) spans full width
            if ($value === '') {
                $section->writeln($label);
                continue;
            }
            $labelPlain = strip_tags($label);
            $pad = str_repeat('.', max(2, 50 - mb_strlen($labelPlain)));
            $section->writeln("  {$label} {$pad} {$value}");
        }
    }

    /**
     * Analyse one File record and accumulate stats by reference.
     */
    private function analyseFile(
        ?File  $file,
        bool   $isCompressed,
        int    &$compressedSize,
        int    &$compressedCount,
        int    &$rawSize,
        int    &$compressible,
        int    &$saving,
        int    &$notEligible,
        int    &$dbStored,
        float  &$msRead,
        float  &$msCanEncode,
        float  &$msCompress,
        int    &$callsRead,
        int    &$callsCanEncode,
        int    &$callsCompress,
    ): void {
        if ($file === null) {
            return;
        }

        if ($file->content !== null) {
            $dbStored++;
        }

        if ($isCompressed) {
            $compressedCount++;
            $compressedSize += $this->storedSize($file);
            return;
        }

        $rawSize += $file->size;

        // ── 1. Read file to temp ─────────────────────────────────────────────
        $callsRead++;
        [$tmpIn, $ms] = Benchmark::value(fn () => $this->writeTempFile($file));
        $msRead += $ms;

        if ($tmpIn === null) {
            $notEligible++;
            return;
        }

        // ── 2. canEncode ─────────────────────────────────────────────────────
        $callsCanEncode++;
        [$canEncode, $ms] = Benchmark::value(fn () => $this->bfc->canEncode($tmpIn));
        $msCanEncode += $ms;

        if (!$canEncode) {
            $notEligible++;
            @unlink($tmpIn);
            return;
        }

        // ── 3. compressedSize (no output file needed) ─────────────────────────
        $callsCompress++;
        try {
            [$bfcSize, $ms] = Benchmark::value(fn () => $this->bfc->compressedSize($tmpIn));
            $msCompress += $ms;

            if ($bfcSize < $file->size) {
                $compressible++;
                $saving += $file->size - $bfcSize;
            } else {
                $notEligible++;
            }
        } catch (\Throwable) {
            $notEligible++;
        } finally {
            @unlink($tmpIn);
        }
    }

    private function renderReport(array $s): void
    {
        $totalIn  = $s['in_compressed']  + ($s['in_compressible'] + $s['in_not_eligible']);
        $totalOut = $s['out_compressed'] + ($s['out_compressible'] + $s['out_not_eligible']);

        $this->newLine();
        $this->components->twoColumnDetail('<fg=cyan;options=bold>TEST CASES</>');
        $this->components->twoColumnDetail('Total',           $s['total']);
        $this->components->twoColumnDetail('Both compressed', $s['both_compressed']);

        $this->newLine();
        $this->components->twoColumnDetail('<fg=yellow>INPUT FILES</>');
        $this->components->twoColumnDetail('Total input files',       $totalIn);
        $this->components->twoColumnDetail('Already compressed',      $s['in_compressed']);
        $this->components->twoColumnDetail('Compressed storage used', $this->fmtBytes($s['in_compressed_size']));
        $this->components->twoColumnDetail('Uncompressed (raw)',       $totalIn - $s['in_compressed']);
        $this->components->twoColumnDetail('Raw storage used',        $this->fmtBytes($s['in_raw_size']));
        $this->components->twoColumnDetail('Can be compressed',       $s['in_compressible']);
        $this->components->twoColumnDetail('Not eligible / binary',   $s['in_not_eligible']);
        $this->components->twoColumnDetail('Potential space saving',  $this->fmtBytes($s['in_saving']));
        $this->components->twoColumnDetail('Stored in DB',            $s['in_db_stored']);

        $this->newLine();
        $this->components->twoColumnDetail('<fg=yellow>OUTPUT FILES</>');
        $this->components->twoColumnDetail('Total output files',      $totalOut);
        $this->components->twoColumnDetail('Already compressed',      $s['out_compressed']);
        $this->components->twoColumnDetail('Compressed storage used', $this->fmtBytes($s['out_compressed_size']));
        $this->components->twoColumnDetail('Uncompressed (raw)',      $totalOut - $s['out_compressed']);
        $this->components->twoColumnDetail('Raw storage used',        $this->fmtBytes($s['out_raw_size']));
        $this->components->twoColumnDetail('Can be compressed',       $s['out_compressible']);
        $this->components->twoColumnDetail('Not eligible / binary',   $s['out_not_eligible']);
        $this->components->twoColumnDetail('Potential space saving',  $this->fmtBytes($s['out_saving']));
        $this->components->twoColumnDetail('Stored in DB',            $s['out_db_stored']);

        $totalRaw     = $s['in_raw_size']  + $s['out_raw_size'];
        $totalSaving  = $s['in_saving']    + $s['out_saving'];
        $totalCmpSize = $s['in_compressed_size'] + $s['out_compressed_size'];
        $totalCmp     = $s['in_compressed'] + $s['out_compressed'];

        $this->newLine();
        $this->components->twoColumnDetail('<fg=green>TOTALS</>');
        $this->components->twoColumnDetail('Total files compressed',        $totalCmp);
        $this->components->twoColumnDetail('Total compressed storage',      $this->fmtBytes($totalCmpSize));
        $this->components->twoColumnDetail('Total uncompressed storage',    $this->fmtBytes($totalRaw));
        $this->components->twoColumnDetail('Total compressible (not yet)',  $s['in_compressible'] + $s['out_compressible']);
        $this->components->twoColumnDetail('Total potential saving',        $this->fmtBytes($totalSaving));

        if ($totalRaw > 0) {
            $pct = round($totalSaving / $totalRaw * 100, 1);
            $this->components->twoColumnDetail('Saving %',  "{$pct}%");
        }

        $avgRead      = $s['calls_read']       > 0 ? $s['ms_read']       / $s['calls_read']       : 0;
        $avgCanEncode = $s['calls_can_encode']  > 0 ? $s['ms_can_encode'] / $s['calls_can_encode'] : 0;
        $avgCompress  = $s['calls_compress']    > 0 ? $s['ms_compress']   / $s['calls_compress']   : 0;

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>TIMING METRICS</>');
        $this->components->twoColumnDetail('Read file   — total / avg / calls', $this->fmtMs($s['ms_read'])       . ' / ' . $this->fmtMs($avgRead)      . ' / ' . $s['calls_read']);
        $this->components->twoColumnDetail('canEncode   — total / avg / calls', $this->fmtMs($s['ms_can_encode']) . ' / ' . $this->fmtMs($avgCanEncode) . ' / ' . $s['calls_can_encode']);
        $this->components->twoColumnDetail('Compress    — total / avg / calls', $this->fmtMs($s['ms_compress'])   . ' / ' . $this->fmtMs($avgCompress)  . ' / ' . $s['calls_compress']);
        $this->components->twoColumnDetail('Total measured time', $this->fmtMs($s['ms_read'] + $s['ms_can_encode'] + $s['ms_compress']));

        $this->newLine();

        if ($s['in_compressible'] + $s['out_compressible'] > 0) {
            $this->line('  Run <fg=cyan>php artisan app:compress-testcases --compress</> to apply compression.');
        } else {
            $this->line('  <fg=green>All eligible test cases are already compressed.</>');
        }

        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // COMPRESS MODE
    // -------------------------------------------------------------------------

    private function runCompress(): int
    {
        $force = $this->option('force');

        $query = TestCase::with(['inputfile', 'outputfile'])
            ->when(!$force, fn ($q) => $q->where(
                fn ($q) => $q->where('is_input_compressed', false)->orWhere('is_output_compressed', false)
            ));

        $compressed = 0;
        $skipped    = 0;
        $errors     = 0;

        $query->chunk(100, function ($cases) use (&$compressed, &$skipped, &$errors, $force) {
            foreach ($cases as $tc) {
                $changed = false;

                if (!$tc->is_input_compressed || $force) {
                    $result = $this->compressFile($tc->inputfile, 'input');
                    if ($result === 'ok') {
                        $tc->is_input_compressed = true;
                        $compressed++;
                        $changed = true;
                    } elseif ($result === 'skip') {
                        $skipped++;
                    } else {
                        $errors++;
                    }
                }

                if (!$tc->is_output_compressed || $force) {
                    $result = $this->compressFile($tc->outputfile, 'output');
                    if ($result === 'ok') {
                        $tc->is_output_compressed = true;
                        $compressed++;
                        $changed = true;
                    } elseif ($result === 'skip') {
                        $skipped++;
                    } else {
                        $errors++;
                    }
                }

                if ($changed) {
                    $tc->save();
                }
            }
        });

        $this->info("Done. Compressed: {$compressed} | Skipped (not eligible): {$skipped} | Errors: {$errors}");
        return self::SUCCESS;
    }

    /**
     * Compress a single File in-place.
     * Returns 'ok', 'skip', or 'error'.
     */
    private function compressFile(?File $file, string $label): string
    {
        if ($file === null) {
            return 'skip';
        }

        $tmpIn = $this->writeTempFile($file);
        if ($tmpIn === null) {
            return 'error';
        }

        if (!$this->bfc->canEncode($tmpIn)) {
            @unlink($tmpIn);
            return 'skip';
        }

        $tmpOut = $tmpIn . '.bfc';
        try {
            $this->bfc->compress($tmpIn, $tmpOut);

            $bfcSize = filesize($tmpOut);
            if ($bfcSize >= $file->size) {
                return 'skip';
            }

            // Replace file content with the BFC binary
            $bfcData = file_get_contents($tmpOut);

            if ($file->content !== null) {
                // DB-stored: replace content
                $file->compacted = false;
                $file->content   = $bfcData;
                $file->compact(); // re-gzcompress the new binary
            } else {
                // Disk-stored: overwrite via Storage
                Storage::put($file->path, $bfcData);
            }

            $file->size = $bfcSize;
            $file->save();

            $this->line("  <fg=green>✓</> {$label} file #{$file->id} compressed ({$this->fmtBytes($bfcSize)})");
            return 'ok';
        } catch (\Throwable $e) {
            $this->line("  <fg=red>✗</> {$label} file #{$file->id}: {$e->getMessage()}");
            return 'error';
        } finally {
            @unlink($tmpIn);
            @unlink($tmpOut);
        }
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    /**
     * Write a File's content to a system temp file and return its path.
     * Returns null on failure.
     */
    private function writeTempFile(File $file): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'bfc_');
        if ($tmp === false) {
            return null;
        }

        try {
            $content = $file->get();
            if ($content === null) {
                @unlink($tmp);
                return null;
            }
            file_put_contents($tmp, $content);
            return $tmp;
        } catch (\Throwable) {
            @unlink($tmp);
            return null;
        }
    }

    /**
     * Return the actual storage size of a File record:
     * DB-stored → length of raw (possibly gzcompressed) content.
     * Disk-stored → file size on disk.
     */
    private function storedSize(File $file): int
    {
        if ($file->content !== null) {
            return strlen($file->content);
        }

        try {
            return Storage::size($file->path);
        } catch (\Throwable) {
            return $file->size;
        }
    }

    private function fmtMs(float $ms): string
    {
        if ($ms >= 60_000) {
            return round($ms / 60_000, 2) . ' min';
        }
        if ($ms >= 1_000) {
            return round($ms / 1_000, 2) . ' s';
        }
        return round($ms, 2) . ' ms';
    }

    private function fmtBytes(int $bytes): string
    {
        if ($bytes >= 1_073_741_824) {
            return round($bytes / 1_073_741_824, 2) . ' GB';
        }
        if ($bytes >= 1_048_576) {
            return round($bytes / 1_048_576, 2) . ' MB';
        }
        if ($bytes >= 1_024) {
            return round($bytes / 1_024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
