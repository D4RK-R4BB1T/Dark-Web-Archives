<?php

namespace App\Http\Requests\Admin;

use App\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class DisplayUsersRequest extends FormRequest
{
    /**
     * How many users to display in a table per single page
     *
     * @var int
     */
    private $displayUsersPerPage = 30;

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
     * Array of supported user groups
     *
     * @var array
     */
    private $availableDisplayGroups = [
        'administrators',
        'vendors',
        'moderators',
        'everyone'
    ];

    /**
     * Default order
     *
     * @var string
     */
    private $orderBy = 'newest';

    /**
     * Default display group
     *
     * @var string
     */
    private $displayGroup = 'everyone';

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => 'string|nullable'
        ];
    }
    public function persist(){
        $orderBy = $this->get('order_by');
        if ($orderBy !== null && in_array($orderBy,$this->availableOrderMethods)){
            $this->orderBy = $orderBy;
        }

        $displayGroup = $this->get('display_group');
        if ($displayGroup !== null && in_array($displayGroup,$this->availableDisplayGroups)){
            $this->displayGroup = $displayGroup;
        }

    }

    public function getUsers(){
        if(!empty($this -> username))
            $users = User::where('username', 'LIKE', '%' . $this -> username . '%') -> with(['admin', 'vendor']) -> get();
        else
            $users = User::with(['admin','vendor'])->get();
        if ($this->displayGroup == 'administrators'){
            $users = $users->filter(function($user){
                return $user->admin !== null;
            });
        }
        if ($this->displayGroup == 'moderators'){
            $users = $users->filter(function($user){
                return $user->hasPermissions() != false;
            });
        }
        if ($this->displayGroup == 'vendors'){
            $users = $users->filter(function($user){
                return $user->vendor !== null;
            });
        }

        if ($this->orderBy == 'newest'){
            $users = $users->sortBy('created_at');
        }
        if ($this->orderBy == 'oldest'){
            $users = $users->sortByDesc('created_at');
        }

        $finalResult = $this->paginate($users,$this->displayUsersPerPage);
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
    private function paginate($items, $perPage = 15, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}
