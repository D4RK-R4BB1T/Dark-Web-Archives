<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Intervention\Image\ImageManager;

class ShopSettingsAppearanceRequest extends FormRequest
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
     * @var string
     */
    public $bannerURL;

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
        return \Auth::user()->shop()->enabled;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|min:3|max:30',
            'banner' => 'sometimes|image|max:800',
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
                    $disk->put($path, $this->formatAvatar($image));
                } catch (\Exception $e) {
                    $validator->errors()->add('image', 'Невозможно сохранить картинку.');
                    return;
                }
                $this->imageURL = $disk->url($path);
            }

            if ($this->hasFile('banner')) {
                $banner = $this->file('banner');
                $path = $banner->hashName('shops');

                $disk = \Storage::disk('public');
                try {
                    $disk->put($path, $this->formatBanner($banner));
                } catch (\Exception $e) {
                    $validator->errors()->add('banner', 'Невозможно сохранить картинку.');
                    return;
                }
                $this->bannerURL = $disk->url($path);
            }

        });

        return $validator;
    }

    /**
     * Resize an image instance for the given file.
     *
     * @param  \SplFileInfo  $file
     * @return string
     */
    protected function formatAvatar($file)
    {
        return (string) $this->images->make($file->path())
            ->fit(190)->encode();
    }

    /**
     * @param \SplFileInfo $file
     * @return string
     */
    protected function formatBanner($file)
    {
        return (string) $this->images->make($file->path())
            ->resize(1152, 470, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode();
    }
}
