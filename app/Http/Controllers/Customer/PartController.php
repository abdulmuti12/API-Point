<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Brand;
use App\Models\Category;

class PartController extends BaseController
{

    public function getBrand()
    {
        $brands = Brand::select('id', 'name')->get();

        return $this->sendResponse($brands, 'Success Load Data');
    }


    public function getType()
    {
        $types = [ 'Custom Made', 'Pre Order', 'Limited Edition','Sales Stock','Ready Stock'];

        return $this->sendResponse($types, 'Success Load Data');

    }

    public function getColor()
    {
        $colors = ['Red', 'Green', 'Blue', 'Yellow', 'Cyan', 'Magenta', 'Black', 'White', 'Gray', 'Orange', 'Purple', 'Pink', 'Brown', 'Lime', 'Olive', 'Teal', 'Navy', 'Maroon', 'Silver', 'Gold', 'Beige'];

        $formattedColors = array_map(function ($color) {
            return ['color' => $color];
        }, $colors);

        return $this->sendResponse($formattedColors, 'Success Load Data');
    }

    public function getCategory()
    {
        $categories = Category::select('id', 'name')->get();

        return $this->sendResponse($categories, 'Success Load Data');

    }
}
