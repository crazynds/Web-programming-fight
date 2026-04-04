<?php

namespace App\Services;

/**
 * Compresses text files (integers, floats, strings) into a compact binary format.
 *
 * File layout:
 *   4 bytes  magic  "BFC\x01"
 *   N bytes  stream of tagged tokens
 *
 * Type tags (1 byte each):
 *   0x00  NEWLINE          – no payload; tokens on a line are implicitly space-separated
 *   0x01  MULTI_SPACE      – 1 byte extra-space count (spaces beyond the implicit one; no implicit space emitted before this tag)
 *   0x02  STRING           – uint16-LE length + N bytes UTF-8
 *   0x03  STRING_CONCAT    – uint16-LE length + N bytes UTF-8 (concatenate with previous string)
 *   0x04  INT1             – 1 signed byte
 *   0x05  INT2             – 2 bytes signed LE
 *   0x06  INT3             – 3 bytes signed LE
 *   0x07  INT4             – 4 bytes signed LE
 *   0x08  INT5             – 5 bytes signed LE
 *   0x09  INT6             – 6 bytes signed LE
 *   0x0A  INT7             – 7 bytes signed LE
 *   0x0B  INT8             – 8 bytes signed LE
 *   0x0C  FLOAT            – 1 byte sign (0=+,1=-) + intN(abs integer part) + 1 byte fracDigits + intN(frac magnitude)
 *   0x0D  VEC_INT1         – uint32-LE count + count×1 signed bytes
 *   0x0E  VEC_INT2         – uint32-LE count + count×2 bytes signed LE
 *   0x0F  VEC_INT3         – uint32-LE count + count×3 bytes signed LE
 *   0x10  VEC_INT4         – uint32-LE count + count×4 bytes signed LE
 *   0x11  VEC_INT5         – uint32-LE count + count×5 bytes signed LE
 *   0x12  VEC_INT6         – uint32-LE count + count×6 bytes signed LE
 *   0x13  VEC_INT7         – uint32-LE count + count×7 bytes signed LE
 *   0x14  VEC_INT8         – uint32-LE count + count×8 bytes signed LE
 *
 * Implicit spacing rule: within a line, a single space is emitted between consecutive tokens on
 * decode.  This removes the need for an explicit SEPARATOR tag in the typical case and saves
 * ~1 byte per whitespace gap.
 *
 * Vector rule: if every data token on a line is an integer, the whole line is encoded as a single
 * VEC_INTn tag using the smallest intN that fits all values.  This saves one tag byte per token.
 */
class BinaryFileCompressor
{
    public const MAGIC = "BFC\x01";

    // Type tag constants
    public const TAG_NEWLINE       = 0x00;
    public const TAG_MULTI_SPACE   = 0x01;  // payload: 1 byte count of EXTRA spaces (total = implicit 1 + count)
    public const TAG_STRING        = 0x02;
    public const TAG_STRING_CONCAT = 0x03;
    public const TAG_INT1          = 0x04;
    public const TAG_INT2          = 0x05;
    public const TAG_INT3          = 0x06;
    public const TAG_INT4          = 0x07;
    public const TAG_INT5          = 0x08;
    public const TAG_INT6          = 0x09;
    public const TAG_INT7          = 0x0A;
    public const TAG_INT8          = 0x0B;
    public const TAG_FLOAT         = 0x0C;
    public const TAG_VEC_INT1      = 0x0D;
    public const TAG_VEC_INT2      = 0x0E;
    public const TAG_VEC_INT3      = 0x0F;
    public const TAG_VEC_INT4      = 0x10;
    public const TAG_VEC_INT5      = 0x11;
    public const TAG_VEC_INT6      = 0x12;
    public const TAG_VEC_INT7      = 0x13;
    public const TAG_VEC_INT8      = 0x14;

    /** [min, max] for each signed intN tag */
    private const INT_LIMITS = [
        self::TAG_INT1 => [-(1 << 7),  (1 << 7)  - 1],
        self::TAG_INT2 => [-(1 << 15), (1 << 15) - 1],
        self::TAG_INT3 => [-(1 << 23), (1 << 23) - 1],
        self::TAG_INT4 => [-(1 << 31), (1 << 31) - 1],
        self::TAG_INT5 => [-(1 << 39), (1 << 39) - 1],
        self::TAG_INT6 => [-(1 << 47), (1 << 47) - 1],
        self::TAG_INT7 => [-(1 << 55), (1 << 55) - 1],
        self::TAG_INT8 => [PHP_INT_MIN, PHP_INT_MAX],
    ];

    /**
     * Callable used by all encoding helpers to emit bytes.
     * Set before each encode pass; either writes to a file or counts bytes.
     */
    private \Closure $writer;

    // -------------------------------------------------------------------------
    // PUBLIC API
    // -------------------------------------------------------------------------

    /**
     * Check whether a file can be losslessly encoded by this compressor.
     *
     * Returns true  when the file is a readable, line-oriented text file with no binary content.
     * Returns false (and sets $reason) when something would prevent a correct round-trip:
     *   - file not found / not readable
     *   - contains null bytes (binary file)
     *   - contains invalid UTF-8 sequences
     *   - a float token has more than 255 decimal digits (format limit)
     *   - a string token exceeds the maximum encodable length (very unlikely in practice)
     */
    public function canEncode(string $inputPath, ?string &$reason = null): bool
    {
        if (!is_file($inputPath) || !is_readable($inputPath)) {
            $reason = "Cannot open file: {$inputPath}";
            return false;
        }

        $in = fopen($inputPath, 'rb');

        try {
            $lineNumber = 0;
            while (($rawLine = fgets($in)) !== false) {
                $lineNumber++;

                // Null bytes → binary file
                if (strpos($rawLine, "\x00") !== false) {
                    $reason = "Binary content (null byte) found at line {$lineNumber}";
                    return false;
                }

                $line = rtrim($rawLine, "\r\n");

                // Invalid UTF-8
                if (!mb_check_encoding($line, 'UTF-8')) {
                    $reason = "Invalid UTF-8 sequence at line {$lineNumber}";
                    return false;
                }

                // Per-token checks
                $tokens = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
                foreach ($tokens as $token) {
                    // Float: fracDigits must fit in 1 byte (≤ 255)
                    if (preg_match('/^-?\d+\.(\d+)$/', $token, $m)) {
                        if (strlen($m[1]) > 255) {
                            $reason = "Float token at line {$lineNumber} has more than 255 decimal digits";
                            return false;
                        }
                    }

                    // String chunk limit: each 65535-byte chunk fits in a uint16; the token itself
                    // can be arbitrarily long (chunked via STRING_CONCAT), so no hard limit here.
                }
            }
        } finally {
            fclose($in);
        }

        $reason = null;
        return true;
    }

    /**
     * Compress a text file and write the binary result to $outputPath.
     */
    public function compress(string $inputPath, string $outputPath): void
    {
        $out = fopen($outputPath, 'wb');
        if ($out === false) {
            throw new \RuntimeException("Cannot open output file: {$outputPath}");
        }

        $this->writer = static function (string $data) use ($out): void {
            fwrite($out, $data);
        };

        try {
            $this->encodeStream($inputPath);
        } finally {
            fclose($out);
            unset($this->writer);
        }
    }

    /**
     * Calculate the size the compressed binary would have without writing any file.
     * Returns -1 if the input file cannot be opened.
     */
    public function compressedSize(string $inputPath): int
    {
        $size = 0;

        $this->writer = static function (string $data) use (&$size): void {
            $size += strlen($data);
        };

        try {
            $this->encodeStream($inputPath);
        } finally {
            unset($this->writer);
        }

        return $size;
    }

    // -------------------------------------------------------------------------
    // INTERNAL WRITE HELPER
    // -------------------------------------------------------------------------

    /** Emit bytes through the active writer (file handle or byte counter). */
    private function write(string $data): void
    {
        ($this->writer)($data);
    }

    // -------------------------------------------------------------------------
    // SHARED ENCODE STREAM
    // -------------------------------------------------------------------------

    private function encodeStream(string $inputPath): void
    {
        $in = fopen($inputPath, 'rb');
        if ($in === false) {
            throw new \RuntimeException("Cannot open input file: {$inputPath}");
        }

        try {
            $this->write(self::MAGIC);

            $firstLine      = true;
            $lastHadNewline = false;
            while (($rawLine = fgets($in)) !== false) {
                if (!$firstLine) {
                    $this->write(chr(self::TAG_NEWLINE));
                }
                $firstLine      = false;
                $lastHadNewline = str_ends_with($rawLine, "\n");

                $this->encodeLine(rtrim($rawLine, "\r\n"));
            }
            if ($lastHadNewline) {
                $this->write(chr(self::TAG_NEWLINE));
            }
        } finally {
            fclose($in);
        }
    }

    /**
     * Decompress a binary file produced by compress() back to text.
     */
    public function decompress(string $inputPath, string $outputPath): void
    {
        $in = fopen($inputPath, 'rb');
        if ($in === false) {
            throw new \RuntimeException("Cannot open input file: {$inputPath}");
        }

        $out = fopen($outputPath, 'wb');
        if ($out === false) {
            fclose($in);
            throw new \RuntimeException("Cannot open output file: {$outputPath}");
        }

        try {
            $magic = fread($in, 4);
            if ($magic !== self::MAGIC) {
                throw new \RuntimeException('Invalid file: missing BFC magic header');
            }

            $firstToken    = true;  // first token on current line?
            $pendingString = null;  // accumulates STRING_CONCAT chunks

            while (($tagByte = fread($in, 1)) !== '' && $tagByte !== false) {
                $tag = ord($tagByte);

                switch ($tag) {
                    case self::TAG_NEWLINE:
                        $this->flushString($pendingString, $out, $firstToken);
                        $pendingString = null;
                        fwrite($out, "\n");
                        $firstToken = true;
                        break;

                    case self::TAG_MULTI_SPACE:
                        // Extra spaces beyond the implicit separator — no implicit space added here.
                        $this->flushString($pendingString, $out, $firstToken);
                        $pendingString = null;
                        $extra = ord(fread($in, 1));
                        fwrite($out, str_repeat(' ', $extra));
                        // firstToken remains false; next token's implicit space completes the gap.
                        break;

                    case self::TAG_STRING:
                    case self::TAG_STRING_CONCAT:
                        $len  = $this->readUint16($in);
                        $data = $len > 0 ? fread($in, $len) : '';

                        if ($tag === self::TAG_STRING) {
                            $this->flushString($pendingString, $out, $firstToken);
                            $pendingString = $data;
                        } else {
                            $pendingString .= $data;
                        }
                        break;

                    case self::TAG_INT1:
                    case self::TAG_INT2:
                    case self::TAG_INT3:
                    case self::TAG_INT4:
                    case self::TAG_INT5:
                    case self::TAG_INT6:
                    case self::TAG_INT7:
                    case self::TAG_INT8:
                        $this->flushString($pendingString, $out, $firstToken);
                        $pendingString = null;

                        $byteCount = $tag - self::TAG_INT1 + 1;
                        $raw       = fread($in, $byteCount);
                        if (strlen($raw) < $byteCount) {
                            throw new \RuntimeException('Unexpected end of file reading integer');
                        }
                        if (!$firstToken) {
                            fwrite($out, ' ');
                        }
                        fwrite($out, (string) $this->decodeSigned($raw, $byteCount));
                        $firstToken = false;
                        break;

                    case self::TAG_FLOAT:
                        $this->flushString($pendingString, $out, $firstToken);
                        $pendingString = null;

                        $negative  = ord(fread($in, 1)) === 1;
                        $intAbs    = $this->readIntTokenValue($in);
                        $fracDigits = ord(fread($in, 1));
                        $fracAbs   = $this->readIntTokenValue($in);

                        if (!$firstToken) {
                            fwrite($out, ' ');
                        }
                        $sign = $negative ? '-' : '';
                        // Re-pad leading zeros in fractional part
                        $fracStr = str_pad((string) $fracAbs, $fracDigits, '0', STR_PAD_LEFT);
                        fwrite($out, "{$sign}{$intAbs}.{$fracStr}");
                        $firstToken = false;
                        break;

                    case self::TAG_VEC_INT1:
                    case self::TAG_VEC_INT2:
                    case self::TAG_VEC_INT3:
                    case self::TAG_VEC_INT4:
                    case self::TAG_VEC_INT5:
                    case self::TAG_VEC_INT6:
                    case self::TAG_VEC_INT7:
                    case self::TAG_VEC_INT8:
                        $this->flushString($pendingString, $out, $firstToken);
                        $pendingString = null;

                        $byteCount = $tag - self::TAG_VEC_INT1 + 1;
                        $rawCount  = fread($in, 4);
                        if (strlen($rawCount) < 4) {
                            throw new \RuntimeException('Unexpected end of file reading vector count');
                        }
                        $count = unpack('V', $rawCount)[1];

                        for ($i = 0; $i < $count; $i++) {
                            $raw = fread($in, $byteCount);
                            if (strlen($raw) < $byteCount) {
                                throw new \RuntimeException('Unexpected end of file reading vector element');
                            }
                            if (!$firstToken || $i > 0) {
                                fwrite($out, ' ');
                            }
                            fwrite($out, (string) $this->decodeSigned($raw, $byteCount));
                        }
                        $firstToken = false;
                        break;

                    default:
                        throw new \RuntimeException(sprintf('Unknown type tag: 0x%02X', $tag));
                }
            }

            // Flush trailing token on last line (no trailing NEWLINE tag)
            $this->flushString($pendingString, $out, $firstToken);
        } finally {
            fclose($in);
            fclose($out);
        }
    }

    // -------------------------------------------------------------------------
    // ENCODING HELPERS
    // -------------------------------------------------------------------------

    private function encodeLine(string $line): void
    {
        if ($line === '') {
            return;
        }

        $parts        = preg_split('/(\s+)/', $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $dataTokens   = [];
        $pendingExtra = 0;

        foreach ($parts as $part) {
            if (trim($part) === '') {
                $pendingExtra += strlen($part) - 1;
            } else {
                $dataTokens[] = [$part, $pendingExtra];
                $pendingExtra = 0;
            }
        }

        if (empty($dataTokens)) {
            return;
        }

        // ── Vector fast-path ────────────────────────────────────────────────
        $allInts   = true;
        $intValues = [];
        $worstTag  = self::TAG_INT1;

        foreach ($dataTokens as [$token, $extra]) {
            if ($extra > 0 || !preg_match('/^-?\d+$/', $token)) {
                $allInts = false;
                break;
            }
            $v = (int) $token;
            if ((string) $v !== $token) {
                $allInts = false;
                break;
            }
            $tag = $this->pickIntTag($v);
            if ($tag === null) {
                $allInts = false;
                break;
            }
            if ($tag > $worstTag) {
                $worstTag = $tag;
            }
            $intValues[] = $v;
        }

        if ($allInts && count($intValues) > 1) {
            $vecTag    = $worstTag - self::TAG_INT1 + self::TAG_VEC_INT1;
            $byteCount = $worstTag - self::TAG_INT1 + 1;
            $this->write(chr($vecTag) . pack('V', count($intValues)) . $this->encodeSignedBulk($intValues, $byteCount));
            return;
        }

        // ── General path ────────────────────────────────────────────────────
        $firstData = true;
        foreach ($dataTokens as [$token, $extra]) {
            if (!$firstData && $extra > 0) {
                $this->write(chr(self::TAG_MULTI_SPACE) . chr(min($extra, 255)));
            }
            $firstData = false;
            $this->encodeToken($token);
        }
    }

    private function encodeToken(string $token): void
    {
        if (preg_match('/^-?\d+$/', $token)) {
            $value = (int) $token;
            if ((string) $value === $token) {
                $this->writeInt($value);
                return;
            }
        }

        if (preg_match('/^(-?)(\d+)\.(\d+)$/', $token, $m)) {
            $negative   = $m[1] === '-';
            $intAbs     = (int) $m[2];
            $fracStr    = $m[3];
            $fracAbs    = (int) $fracStr;
            $fracDigits = strlen($fracStr);

            $intTag  = $this->pickIntTag($intAbs);
            $fracTag = $this->pickIntTag($fracAbs);

            if ($intTag !== null && $fracTag !== null && $fracDigits <= 255) {
                $this->write(chr(self::TAG_FLOAT) . chr($negative ? 1 : 0));
                $this->writeInt($intAbs);
                $this->write(chr($fracDigits));
                $this->writeInt($fracAbs);
                return;
            }
        }

        $this->writeString($token);
    }

    private function writeInt(int $value): void
    {
        $tag = $this->pickIntTag($value);
        if ($tag === null) {
            $this->writeString((string) $value);
            return;
        }
        $byteCount = $tag - self::TAG_INT1 + 1;
        $this->write(chr($tag) . $this->encodeSigned($value, $byteCount));
    }

    private function pickIntTag(int $value): ?int
    {
        foreach (self::INT_LIMITS as $tag => [$min, $max]) {
            if ($value >= $min && $value <= $max) {
                return $tag;
            }
        }
        return null;
    }

    private function writeString(string $value): void
    {
        $total      = strlen($value);
        $offset     = 0;
        $firstChunk = true;

        if ($total === 0) {
            $this->write(chr(self::TAG_STRING) . pack('v', 0));
            return;
        }

        while ($offset < $total) {
            $chunk = substr($value, $offset, 65535);
            $len   = strlen($chunk);
            $tag   = $firstChunk ? self::TAG_STRING : self::TAG_STRING_CONCAT;
            $this->write(chr($tag) . pack('v', $len) . $chunk);
            $offset    += $len;
            $firstChunk = false;
        }
    }

    // -------------------------------------------------------------------------
    // DECODING HELPERS
    // -------------------------------------------------------------------------

    /**
     * Flush a pending string to output, emitting an implicit space separator if needed.
     * Resets $firstToken to false after flushing.
     */
    private function flushString(?string &$pending, $out, bool &$firstToken): void
    {
        if ($pending === null) {
            return;
        }
        if (!$firstToken) {
            fwrite($out, ' ');
        }
        fwrite($out, $pending);
        $firstToken = false;
        $pending    = null;
    }

    /** Read a uint16 little-endian from the stream. */
    private function readUint16($in): int
    {
        $raw = fread($in, 2);
        if (strlen($raw) < 2) {
            throw new \RuntimeException('Unexpected end of file reading uint16');
        }
        return unpack('v', $raw)[1];
    }

    /** Read an intN tag+value pair and return just the integer value. */
    private function readIntTokenValue($in): int
    {
        $tagByte = fread($in, 1);
        if ($tagByte === false || $tagByte === '') {
            throw new \RuntimeException('Unexpected end of file reading int token tag');
        }
        $tag = ord($tagByte);
        if ($tag < self::TAG_INT1 || $tag > self::TAG_INT8) {
            throw new \RuntimeException(sprintf('Expected intN tag, got 0x%02X', $tag));
        }
        $byteCount = $tag - self::TAG_INT1 + 1;
        $raw       = fread($in, $byteCount);
        if (strlen($raw) < $byteCount) {
            throw new \RuntimeException('Unexpected end of file reading int token payload');
        }
        return $this->decodeSigned($raw, $byteCount);
    }

    // -------------------------------------------------------------------------
    // SIGNED INTEGER CODEC
    // -------------------------------------------------------------------------

    private function encodeSigned(int $value, int $byteCount): string
    {
        // Use pack() for sizes that have a native little-endian format — much faster than a byte loop.
        return match ($byteCount) {
            1 => pack('c', $value),
            2 => pack('v', $value & 0xFFFF),
            4 => pack('V', $value & 0xFFFFFFFF),
            8 => pack('P', $value),
            default => $this->encodeSignedLoop($value, $byteCount),
        };
    }

    /** Byte-loop fallback for odd sizes (3, 5, 6, 7). */
    private function encodeSignedLoop(int $value, int $byteCount): string
    {
        $bytes = '';
        for ($i = 0; $i < $byteCount; $i++) {
            $bytes .= chr($value & 0xFF);
            $value >>= 8;
        }
        return $bytes;
    }

    /**
     * Encode an array of integers all at the same byte width in one shot.
     * Uses vectorised pack() for sizes 1/2/4/8; falls back to per-element loop otherwise.
     */
    private function encodeSignedBulk(array $values, int $byteCount): string
    {
        return match ($byteCount) {
            1 => pack('c*', ...$values),
            2 => pack('v*', ...array_map(fn($v) => $v & 0xFFFF,       $values)),
            4 => pack('V*', ...array_map(fn($v) => $v & 0xFFFFFFFF,   $values)),
            8 => pack('P*', ...$values),
            default => array_reduce(
                $values,
                fn($carry, $v) => $carry . $this->encodeSignedLoop($v, $byteCount),
                '',
            ),
        };
    }

    private function decodeSigned(string $raw, int $byteCount): int
    {
        $value = 0;
        for ($i = $byteCount - 1; $i >= 0; $i--) {
            $value = ($value << 8) | ord($raw[$i]);
        }
        // Sign-extend
        $signBit = 1 << ($byteCount * 8 - 1);
        if ($value & $signBit) {
            $value -= (1 << ($byteCount * 8));
        }
        return $value;
    }
}
