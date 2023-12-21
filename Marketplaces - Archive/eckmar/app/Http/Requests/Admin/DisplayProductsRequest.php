<?php

namespace App\Http\Requests\Admin;

use App\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class DisplayProductsRequest extends FormRequest
{


    /**
     * How many users to display in a table per single page
     *
     * @var int
     */
    private $displayProductsPerPage = 30;

    /**
     * Array of methods supported for ordering
     *
     * @var array
     */
    private $availableOrderMethods = [
        'newest',
        'oldest'
    ];
    /**
     * Default order
     *
     * @var string
     */
    private $orderBy = 'newest';



    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'product' => 'string|nullable'
        ];
    }

    public function persist() {
        $orderBy = $this->get('order_by');
        if ($orderBy !== null && in_array($orderBy, $this->availableOrderMethods)) {
            $this->orderBy = $orderBy;
        }

    }

    public function getProducts() {
        $products = Product::with(['user','category']);
        if ($this->user !== null && $this->user !== '' ){
            $products->where('user_id',$this->user);
        }

        if(!empty($this -> product)){
            $products -> where('name', 'LIKE', '%' . $this->product . '%');
        }

        $products = $products->get();

        if ($this->orderBy == 'newest') {
            $products = $products->sortBy('created_at');
        }
        if ($this->orderBy == 'oldest') {
            $products = $products->sortByDesc('created_at');
        }

        $finalResult = $this->paginate($products, $this->displayProductsPerPage);
        $finalResult->setPath($this->fullUrl());
        return $finalResult;
    }

    /**
     * Paginates a collection
     *
     * @param $items
     * @param int $perPage
     * @param null $page
     * @param array $options
     * @return LengthAwarePaginator
     */
    private function paginate($items, $perPage = 15, $page = null, $options = []) {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}
