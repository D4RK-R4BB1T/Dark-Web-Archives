<?php

namespace App\Events\Admin;

use App\Product;
use App\User;
use Illuminate\Foundation\Events\Dispatchable;

class ProductDeleted
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    /**
     * Admin performing the request
     *
     * @var User
     */
    public $admin;

    /**
     * Product being deleted
     *
     * @var Product
     */
    public $product;

    /**
     * Vendor owning the product
     *
     * @var User
     */
    public $vendor;

    public function __construct(Product $product,User $vendor,User $admin)
    {
        $this->product = $product;
        $this->admin = $admin;
        $this->vendor = $vendor;
    }


}
