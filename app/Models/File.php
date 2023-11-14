<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    public $timestamps = false;
    public $guarded = [];


    public static function createFile(UploadedFile $upfile,string $path,bool $forceDisk = false){
        $file = new File();

        // less than 2KB
        if($upfile->getSize()< 1024*2 && !$forceDisk){
            $file->path = $path.'/'.$upfile->hashName().'_db';
            $file->content = $upfile->get();
        }else{
            $file->path = $upfile->store($path);
            $file->content = null;
        }

        $file->size = $upfile->getSize();
        $file->type = $upfile->getClientOriginalExtension();
        $file->hash = hash_file("sha256",$upfile->getPathname());
        $file->save();
        return $file;
    }

    public function download(string $title){
        if(is_null($this->content)){
            return Storage::download($this->path,$title);
        }else{
            return response()->streamDownload(function () {
                echo $this->content;
            }, $title);
        }
    }

    public function get(){
        if(is_null($this->content)){
            return Storage::get($this->path);
        }else{
            return $this->content;
        }
    }

    public function readStream(){
        if(is_null($this->content)){
            return Storage::readStream($this->path);
        }else{
            $stream = fopen('php://memory','r+');
            fwrite($stream, $this->content);
            rewind($stream);
            return $stream;
        }
    }

}
