<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Banner;


class HomeController extends BaseController
{
    public function getHomeBanner()
    {
        $banners = Banner::where('status', 'Active')->get();


    // 2. Siapkan array untuk menampung slide yang sudah ditransformasi
    $slides = [];

    // 3. Iterasi setiap banner
    foreach ($banners as $banner) {
        for ($i = 1; $i <= 5; $i++) {
            $fileKey  = ($i === 1) ? 'file'  : "file{$i}";
            $noteKey  = ($i === 1) ? 'note'  : "note{$i}";
            $titleKey = ($i === 1) ? 'title' : "title{$i}";

            $fileValue  = $banner->{$fileKey};
            $noteValue  = $banner->{$noteKey};
            $titleValue = $banner->{$titleKey};

            if (!empty($fileValue)) {
                // Normalisasi path
                $path = ltrim($fileValue, '/');
                if (!Str::startsWith($path, 'storage/')) {
                    $path = 'storage/' . $path;
                }

                $slides[] = [
                    'src'         => asset($path),
                    'alt'         => $banner->name ?? '',
                    'type'        => $banner->type ?? 'main',
                    'title'       => $titleValue ?? '',
                    'subtitle'    => ' ',
                    'description' => $noteValue ?? '',
                    'tag'         => '',
                ];
            }
        }
    }

    // 6. Kembalikan respons dengan array slides yang sudah ditransformasi
    return $this->sendResponse($slides, 'Success Load Slides');
    }
}
