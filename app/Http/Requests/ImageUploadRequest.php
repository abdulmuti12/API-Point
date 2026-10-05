<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ImageUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedMimes = 'mimes:jpeg,jpg,png,webp,gif,svg';
        $maxSize = 5120; // 5MB in KB

        return [
            'image1' => "nullable|file|{$allowedMimes}|max:{$maxSize}",
            'image2' => "nullable|file|{$allowedMimes}|max:{$maxSize}",
            'image3' => "nullable|file|{$allowedMimes}|max:{$maxSize}",
            'image4' => "nullable|file|{$allowedMimes}|max:{$maxSize}",
            'image5' => "nullable|file|{$allowedMimes}|max:{$maxSize}",
            'image6' => "nullable|file|{$allowedMimes}|max:{$maxSize}",
            'image' => "nullable|file|{$allowedMimes}|max:{$maxSize}",
            'file' => "nullable|file|{$allowedMimes}|max:{$maxSize}",
        ];
    }

    public function messages(): array
    {
        return [
            'image1.mimes' => 'Image 1 must be a file of type: jpeg, jpg, png, webp, gif, svg',
            'image1.max' => 'Image 1 must not exceed 5MB',
            'image2.mimes' => 'Image 2 must be a file of type: jpeg, jpg, png, webp, gif, svg',
            'image2.max' => 'Image 2 must not exceed 5MB',
            'image3.mimes' => 'Image 3 must be a file of type: jpeg, jpg, png, webp, gif, svg',
            'image3.max' => 'Image 3 must not exceed 5MB',
            'image4.mimes' => 'Image 4 must be a file of type: jpeg, jpg, png, webp, gif, svg',
            'image4.max' => 'Image 4 must not exceed 5MB',
            'image5.mimes' => 'Image 5 must be a file of type: jpeg, jpg, png, webp, gif, svg',
            'image5.max' => 'Image 5 must not exceed 5MB',
            'image6.mimes' => 'Image 6 must be a file of type: jpeg, jpg, png, webp, gif, svg',
            'image6.max' => 'Image 6 must not exceed 5MB',
            'image.mimes' => 'Image must be a file of type: jpeg, jpg, png, webp, gif, svg',
            'image.max' => 'Image must not exceed 5MB',
            'file.mimes' => 'File must be a file of type: jpeg, jpg, png, webp, gif, svg',
            'file.max' => 'File must not exceed 5MB',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}