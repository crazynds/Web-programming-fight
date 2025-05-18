<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use STS\ZipStream\ZipStream;

class File extends Model
{
    public $timestamps = false;

    public $guarded = [];

    // Save at max 16KB in database of content
    const MAX_DB_CONTENT = 16 * 1024;

    public static function createFile(UploadedFile $upfile, string $path, bool $forceDisk = false, bool $preventCompact = false)
    {
        $file = new File;
        $preventCompact |= $forceDisk;
        $storeInDb = false;
        if ($upfile->getSize() < self::MAX_DB_CONTENT * 2 && ! $preventCompact) {
            $file->path = $path.'/'.$upfile->hashName().'_db';
            $file->content = $upfile->get();
            $file->compact();
            if (strlen($file->content) > self::MAX_DB_CONTENT) {
                $file->compacted = false;
                $file->content = null;
                $storeInDb = false;
            } else {
                $storeInDb = true;
            }
        }
        if (! $storeInDb) {
            $file->path = $upfile->store($path);
            $file->content = null;
        }

        $file->size = $upfile->getSize();
        $file->type = $upfile->getClientOriginalExtension();
        $file->hash = hash_file('sha256', $upfile->getPathname());
        $file->save();

        return $file;
    }

    public static function createFileByData($data, string $path, bool $forceDisk = false, bool $preventCompact = false)
    {
        $file = new File;
        $preventCompact |= $forceDisk;
        $storeInDb = false;

        $hashName = sha1(hash('sha256', $data).'-'.strlen($data).'-'.random_bytes(4));

        if (strlen($data) < self::MAX_DB_CONTENT * 2 && ! $preventCompact) {
            $file->path = $path.'/'.$hashName.'_db';
            $file->content = $data;
            $file->compact();
            if (strlen($file->content) > self::MAX_DB_CONTENT) {
                $file->compacted = false;
                $file->content = null;
                $storeInDb = false;
            } else {
                $storeInDb = true;
            }
        }
        if (! $storeInDb) {
            $file->path = $path.'/'.$hashName;
            Storage::put($path.'/'.$hashName, $data);
            $file->content = null;
        }

        $file->size = strlen($data);
        $file->type = 'blob';
        $file->hash = hash('sha256', $data);
        $file->save();

        return $file;
    }

    public static function createFileByStream($stream, int $size, string $hash, string $path, bool $forceDisk = false)
    {
        $file = new File;

        $hashName = sha1($hash.'-'.$size.'-'.random_bytes(4));

        if ($size < self::MAX_DB_CONTENT && ! $forceDisk) {
            $file->path = $path.'/'.$hashName.'_streamed_db';
            $file->content = '';
            do {
                $file->content .= fread($stream, $size);
            } while (strlen($file->content) < $size);
            $file->compact();
            fclose($stream);
        } else {
            Storage::put($path.'/'.$hashName, $stream);
            $file->path = $path.'/'.$hashName;
            $file->content = null;
        }

        $file->size = $size;
        $file->type = '';
        $file->hash = $hash;
        $file->save();

        return $file;
    }

    public function download(string $title)
    {
        if (is_null($this->content)) {
            return Storage::download($this->path, $title);
        } else {
            return response()->streamDownload(function () {
                echo $this->get();
            }, $title);
        }
    }

    public function get()
    {
        if (is_null($this->content)) {
            return Storage::get($this->path);
        } else {
            if ($this->compacted) {
                return gzuncompress($this->content);
            } else {
                return $this->content;
            }
        }
    }

    public function readStream()
    {
        if (is_null($this->content)) {
            return Storage::readStream($this->path);
        } else {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $this->get());
            rewind($stream);

            return $stream;
        }
    }

    public function addToZip(ZipStream $zip, $pathName = null)
    {
        if (is_null($pathName)) {
            $pathName = $this->path;
        }

        if (! is_null($this->content)) {
            $zip->addRaw($this->get(), $pathName);
        } else {
            $zip->add($this->url(), $pathName);
        }
    }

    public function url()
    {
        $bucket = Storage::getAdapter();
        switch (config('filesystems.default')) {
            case 's3':
                $bucket = config('filesystems.disks.s3.bucket');
                $url = "s3://{$bucket}/{$this->path}";
                break;
            case 'local':
                $url = Storage::url($this->path);
        }

        return $url;
    }

    public function compact()
    {
        if (is_null($this->content)) {
            return;
        }
        if ($this->compacted) {
            return;
        }
        $this->compacted = true;
        $this->content = gzcompress($this->content, 9);
    }

    public function extract()
    {
        if (is_null($this->content)) {
            return;
        }
        if (! $this->compacted) {
            return;
        }

        $this->compacted = false;
        $this->content = gzuncompress($this->content);
    }
}
