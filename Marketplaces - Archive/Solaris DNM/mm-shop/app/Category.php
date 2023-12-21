<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * App\Category
 *
 * @property integer $id
 * @property integer $parent_id
 * @property string $title
 * @property integer $priority
 * @method static \Illuminate\Database\Query\Builder|\App\Category whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Category whereParentId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Category whereTitle($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Category wherePriority($value)
 * @mixin \Eloquent
 */
class Category extends Model
{
    protected $table = 'categories';
    protected $primaryKey = 'id';

    public $timestamps = false;

    public $fillable = [
        'parent_id', 'title', 'priority'
    ];

    /** @var Collection */
    private static $_categories = null;
    /** @var Collection */
    private static $_parents = null;
    /** @var Collection */
    private static $_childrens = null;

    private static function group() {
        if (self::$_categories == null || self::$_parents == null || self::$_childrens == null) {
            self::$_categories = \Cache::remember('categories', 60, function() {
                return Category::get();
            });

            self::$_parents = (clone self::$_categories)->filter(function ($item) {
                return $item->parent_id === NULL;
            });

            self::$_childrens = (clone self::$_categories)->filter(function ($item) {
                return $item->parent_id !== NULL;
            });
        }
    }
    /**
     * Return main categories
     * @return Category[]|Collection
     */
    public static function main()
    {
        self::group();
        return self::$_parents;
    }

    /**
     * @return Category[]|Collection
     */
    public static function allChildren()
    {
        self::group();
        return self::$_childrens;
    }

    public static function getById($categoryId)
    {
        self::group();
        if (is_array($categoryId)) {
            return (clone self::$_categories)->whereIn('id', $categoryId);
        }

        return (clone self::$_categories)->where('id', $categoryId);
    }

    /**
     * Returns children categories
     * @throws \Exception
     */
    public function children()
    {
        self::group();
        if (!$this->isMain()) {
            throw new \Exception('This category has no children.');
        }

        return (clone self::$_categories)->where('parent_id', $this->id);
    }

    /**
     * @return Category
     */
    public function parent()
    {
        self::group();
        return self::getById($this->parent_id)->first();
    }

    /**
     * Return true if current category is one of main categories
     * @return bool
     */
    public function isMain()
    {
        return $this->parent_id === NULL;
    }
}
