@extends('master.admin')

@section('admin-content')

    @include('includes.flash.success')
    @include('includes.flash.error')
    <h3 class="mb-5">Categories</h3>

    <div class="row">
        <div class="col-md-6">
            <h4>List of categories ({{ count($categories) }})</h4>
            <hr>
            @if($rootCategories -> isNotEmpty())
                @include('includes.admin.listcategories', ['categories' => $rootCategories])
            @else
                <div class="alert alert-warning text-center">There are no categories!</div>
            @endif
        </div>
        <div class="col-md-6">
            <h4>Add new category</h4>
            <hr>
            <form action="{{ route('admin.categories.new') }}"  method="POST">
                {{ csrf_field() }}
                <input name="name" placeholder="Category name" class="form-control mb-3 @error('name', $errors) is-invalid @enderror"/>
                @error('name', $errors)
                <div class="invalid-feedback d-block">{{ $errors -> first('name') }}</div>
                @enderror
                <label for="parent_id">Parent category:</label>
                <select name="parent_id" class="form-control mb-3" id="parent_id">
                    <option value="" selected>No parent category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category -> id }}">{{ $category -> name }}</option>
                    @endforeach
                </select>
                <button class="btn btn-outline-success d-flex float-right" type="submit">Add category</button>
            </form>
        </div>
    </div>


@stop