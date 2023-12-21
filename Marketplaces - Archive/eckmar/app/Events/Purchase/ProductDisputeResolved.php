<?php

namespace App\Events\Purchase;

use App\Purchase;
use Illuminate\Foundation\Events\Dispatchable;

class ProductDisputeResolved
{
    use Dispatchable;


    /**
     * User who purchased the product
     *
     * @var mixed
     */
    public $buyer;

    /**
     * User who sells the product
     *
     * @var mixed
     */
    public $vendor;

    /**
     * Product
     *
     * @var
     */
    public $product;

    /**
     * Complete instance of a purchase
     *
     * @var
     */
    public $purchase;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Purchase $purchase) {

        $this->buyer = $purchase->buyer;
        $this->vendor = $purchase->vendor;
        $this->product = $purchase->offer->product;
        $this->purchase = $purchase;
    }
}
