<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Intervention\Image\ImageManager;

class ShopInitRequest extends FormRequest
{
    /**
     * @var ImageManager
     */
    protected $images;

    /**
     * @var string
     */
    public $imageURL;

    /**
     * ShopInitRequest constructor.
     * @param ImageManager $images
     */
    public function __construct(ImageManager $images)
    {
        $this->images = $images;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return \Auth::user()->shop()->enabled == FALSE;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|min:3',
            'slug' => 'required|min:3|max:16|alpha_dash|unique:shops',
            'image' => 'sometimes|image|max:400'
        ];
    }

    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();
        $validator->after(function($validator) {
            if ($this->hasFile('image')) {
                $image = $this->file('image');
                $path = $image->hashName('shops');

                $disk = \Storage::disk('public');
                try {
                    $disk->put($path, $this->formatImage($image));
                } catch (\Exception $e) {
                    $validator->errors()->add('image', 'Невозможно сохранить картинку');
                    return;
                }
                $this->imageURL = $disk->url($path);
            }
        });

        return $validator;
    }

    /**
     * Resize an image instance for the given file.
     *
     * @param  \SplFileInfo  $file
     * @return \Intervention\Image\Image
     */
    protected function formatImage($file)
    {
        return (string) $this->images->make($file->path())
            ->fit(190)->encode();
    }

}
