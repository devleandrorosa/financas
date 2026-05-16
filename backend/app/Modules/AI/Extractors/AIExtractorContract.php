<?php

namespace App\Modules\AI\Extractors;

interface AIExtractorContract
{
    public function extract(string $fileName, string $fileContent, string $categoryNames): array;
}
