<?php

namespace App\Models;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use STS\ZipStream\ZipStream;

class File extends Model
{
    public $timestamps = false;
    public $guarded = [];

    // Save at max 2KB in database of content
    const MAX_DB_CONTENT = 2 * 1024;


    public static function createFile(UploadedFile $upfile, string $path, bool $forceDisk = false)
    {
        $file = new File();

        // less than 2KB
        if ($upfile->getSize() < self::MAX_DB_CONTENT && !$forceDisk) {
            $file->path = $path . '/' . $upfile->hashName() . '_db';
            $file->content = $upfile->get();
        } else {
            $file->path = $upfile->store($path);
            $file->content = null;
        }

        $file->size = $upfile->getSize();
        $file->type = $upfile->getClientOriginalExtension();
        $file->hash = hash_file("sha256", $upfile->getPathname());
        $file->save();
        return $file;
    }



    public static function createFileByStream($stream, int $size, string $hash, string $path, bool $forceDisk = false)
    {
        $file = new File();

        $hashName = sha1($hash . '-' . $size . '-' . random_bytes(4));
        // less than 2KB
        if ($size < self::MAX_DB_CONTENT && !$forceDisk) {
            $file->path = $path . '/' . $hashName . '_streamed_db';
            $file->content = fread($stream, $size);
            fclose($stream);
        } else {
            Storage::put($path . '/' . $hashName, $stream);
            $file->path = $path . '/' . $hashName;
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
                echo $this->content;
            }, $title);
        }
    }

    public function get()
    {
        if (is_null($this->content)) {
            return Storage::get($this->path);
        } else {
            return $this->content;
        }
    }

    public function readStream()
    {
        if (is_null($this->content)) {
            return Storage::readStream($this->path);
        } else {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $this->content);
            rewind($stream);
            return $stream;
        }
    }

    public function addToZip(ZipStream $zip, $pathName = null)
    {
        if (is_null($pathName)) $pathName = $this->path;

        if (!is_null($this->content)) {
            $zip->addRaw($this->content, $pathName);
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
}
