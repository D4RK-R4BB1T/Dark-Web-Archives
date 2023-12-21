@extends('layouts.master')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-24">
                <div class="card">
                    <div class="card-header">Файлы</div>

                    <div class="card-body">
{{--                        @include('files.components.component-search')--}}
{{--                        @include('components.post-errors')--}}
                        <div class="row">
                            <div class="col-24">
                                <table class="table table-striped table-hover table-sm table-responsive">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Название</th>
                                            <th scope="col">Размер</th>
                                            <th scope="col" class="text-right">Дата</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($fileList as $k=>$filePath)
                                        <tr>
                                            <th scope="row">
                                                {{ $k }}
                                            </th>
                                            <td onclick="window.location = '{{ route("admin.log-reader.view", ['log_id' => $k]) }}'" class="cursor-pointer">
                                                <a href="{{ route("admin.log-reader.view", ['log_id' => $k]) }}">
                                                    {{ $filePath }}
                                                </a>
                                            </td>
                                            <td>
                                                {{ human_filesize(File::size($filePath)) }}
                                            </td>
                                            <td class="text-right">
                                                {{ \Carbon\Carbon::parse(File::lastModified($filePath))->format('d.m.Y H:i:s') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div><!-- /.col-12 -->
                        </div><!-- /.row -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
