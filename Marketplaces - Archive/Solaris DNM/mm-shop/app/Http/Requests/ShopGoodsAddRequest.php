<?php

namespace App\Http\Requests;

use App\Category;
use Illuminate\Foundation\Http\FormRequest;
use Intervention\Image\ImageManager;

class ShopGoodsAddRequest extends FormRequest
{
    /**
     * @var ImageManager
     */
    protected $images;

    /**
     * @var string
     */
    public $imageURL = null;

    /**
     * @var string[]
     */
    public $additionalImagesURL = [];

    /**
     * ShopGoodsAddRequest constructor.
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

    protected function getValidatorInstance()
    {
        $validator = parent::getValidatorInstance();
        $validator->after(function($validator) {
            /** @var \Illuminate\Validation\Validator $validator */

            if ($validator->errors()->count() > 0) {
                return;
            }

            if (count($this->file('additional_images')) > 3) {
                $validator->errors()->add('additional_images', 'Загружено слишком много дополнительных картинок.');
                return;
            }

            if ($this->hasFile('image')) {
                $image = $this->file('image');
                $path = $image->hashName('goods');

                $disk = \Storage::disk('public');
                try {
                    $disk->put($path, $this->formatMainImage($image));
                } catch (\Exception $e) {
                    $validator->errors()->add('image', 'Невозможно сохранить картинку.');
                    return;
                }

                $this->imageURL = $disk->url($path);
            }

            if ($this->hasFile('additional_images')) {
                foreach ($this->file('additional_images') as $additionalImage) {
                    $path = $additionalImage->hashName('goods_additional');
                    $disk = \Storage::disk('public');
                    try {
                        $disk->put($path, $this->formatAdditionalImage($additionalImage));
                    } catch (\Exception $e) {
                        $validator->errors()->add('additional_images', 'Невозможно сохранить картинку.');
                        return;
                    }

                    $this->additionalImagesURL[] = $disk->url($path);
                }
            }
        });

        return $validator;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $categoryChildrenIds = Category::allChildren()->pluck('id')->toArray();

        return [
            'title' => 'required|min:5',
            'category' => 'required|in:' . implode(',', $categoryChildrenIds),
            'image' => 'required|image',
            'additional_images.*' => 'image',
            'description' => 'required|min:10',
            'priority' => 'numeric|min:0'
        ];
    }

    /**
     * Resize an image instance for the given file.
     *
     * @param  \SplFileInfo  $file
     * @return \Intervention\Image\Image
     */
    protected function formatMainImage($file)
    {
        return (string) $this->images->make($file->path())
            ->fit(300)->encode('jpg');
    }

    /**
     * Resize an image instance for the given file.
     *
     * @param  \SplFileInfo  $file
     * @return \Intervention\Image\Image
     */
    protected function formatAdditionalImage($file)
    {
        return (string) $this->images->make($file->path())->widen(600)->encode('jpg');
    }

}
