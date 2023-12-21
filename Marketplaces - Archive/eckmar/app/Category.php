<?php

namespace App;

use App\Traits\Uuids;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Uuids;
    public $incrementing = false;
    protected $primaryKey = 'id';
    protected $keyType = 'string';


    /**
     * Returns collection of root categories
     *
     * @return \Illuminate\Support\Collection
     */
    public static function roots()
    {
        return self::whereNull('parent_id') -> get();
    }

    /**
     * Returns the collection of all categories A-Z ordered
     *
     * @return \Illuminate\Support\Collection
     */
    public static function nameOrdered()
    {
        return self::orderBy('name') -> get();
    }


    /**
     * @return \App\Category parent category, null for root category
     */
    public function parent()
    {
        return $this -> hasOne(self::class, 'id', 'parent_id');
    }

    public function parents()
    {
        $ancestorsCollection = collect();
        $currentParent = $this -> parent;
        while($currentParent != null){
            $ancestorsCollection -> push($currentParent);
            $currentParent = $currentParent -> parent;
        }

        return $ancestorsCollection -> reverse();
    }

    /**
     * @return collection of category's children
     */
    public function getChildrenAttribute()
    {
        return self::where('parent_id', $this -> id) -> get();
    }

    /**
     * Relationship with products
     *
     * @return collection of \App\Product that belongs to this category
     */
    public function products()
    {
        return $this -> hasMany(\App\Product::class, 'category_id', 'id')
            -> where('active', true);
    }

    /**
     * @var \App\Category $childCategory
     * @return boolean, true if this category is ancestor of $childCategory
     */
    public function isAncestorOf($childCategory)
    {
        if(is_null($childCategory)) return false;
        // starting from parent of the child category
        $tempCategory = $childCategory;

        // while is not root
        while($tempCategory){
            // true, if tempCategory equals this category
            if($tempCategory -> id == $this -> id)
                return true;
            $tempCategory = $tempCategory -> parent;
        }
        // otherwise $this is not ancestor
        return false;
    }

    /**
     * @return int num products this cateogy and all subcategories sumed up
     */
    public function getNumProductsAttribute()
    {
        $numProducts = count($this -> products);

        $otherCategories = Category::where('id', '<>', $this -> id) -> get();
        foreach($otherCategories as $categ){
            if($this -> isAncestorOf($categ))
                $numProducts += count($categ -> products);
        }

        return $numProducts;
    }

    /**
     * Returns collection of all ancestors, gets the recursivly
     *
     * @return mixed
     */
    public function allChildren()
    {
        // get all children
        $children = $this -> children;
        // foreach child category call recursivly
        foreach ($this -> children as $childCategory){
            $children = $children-> merge($childCategory -> allChildren());
        }
        return $children;
    }

    /**
     * Array of all subcategories ids
     *
     * @return mixed
     */
    public function allChildrenIds() : array
    {
        return $this -> allChildren() -> pluck('id') -> toArray();
    }

    /**
     * Array of all subcategories names
     *
     * @return mixed
     */
    public function allChildrenNames() : array
    {
        return $this -> allChildren() -> pluck('name') -> toArray();
    }


    /**
     * Returns paginated collection of products of this category and all children categories
     *
     * @return mixed
     */
    public function childProducts()
    {
        $allAcceptedCategoriesIds = array_merge([$this -> id], $this -> allChildrenIds());

        return Product::where('active', true) -> whereIn('category_id', $allAcceptedCategoriesIds) -> orderByDesc('created_at')
            -> paginate(config('marketplace.products_per_page'));
    }
}
