<?php

namespace App;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class Image extends Model
{
    use Uuids;
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * Returns the product that holds this image
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product()
    {
        return $this -> belongsTo(\App\Product::class, 'product_id', 'id');
    }

    /**
     *  Set the product for this images
     *
     * @param Product $product
     */
    public function setProduct(Product $product)
    {
        $this -> product_id = $product -> id;
    }


}
