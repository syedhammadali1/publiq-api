<?php

namespace App\Http\Traits;

use Spatie\MediaLibrary\Media;
use Spatie\MediaLibrary\PathGenerator\PathGenerator;

class EnvironmentPathGenerator implements PathGenerator
{
    protected $path;

    public function __construct()
    {
        $this->path = app()->env . '/';
    }

    public function getPath(Media $media) : string
    {
        return $this->path . $media->id . '/';
    }

    public function getPathForConversions(Media $media) : string
    {
        return $this->getPath($media) . 'conversions/';
    }
}
