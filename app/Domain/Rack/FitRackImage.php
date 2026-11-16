<?php

namespace App\Domain\Rack;

use App\Domain\Media\ImageStore;
use App\Domain\Rack\Image\RackImagePipeline;
use App\Domain\Rack\Image\TransparencyChecker;
use Illuminate\Http\UploadedFile;

/** Cleans, checks and fits one garment image, then stores it with its smaller copies (C.5, C.8). */
class FitRackImage
{
    public function __construct(
        private readonly ImageStore $images,
        private readonly RackImagePipeline $pipeline,
        private readonly TransparencyChecker $transparency,
    ) {}

    /** @return array{path: string, transparent: bool} */
    public function __invoke(UploadedFile|string $file): array
    {
        $image = $this->images->decode($file);
        $transparent = $this->transparency->hasTransparentBackground($image);
        $fitted = $this->pipeline->process($image);

        return [
            'path' => $this->images->put($fitted->image, 'rack', $fitted->variants),
            'transparent' => $transparent,
        ];
    }
}
