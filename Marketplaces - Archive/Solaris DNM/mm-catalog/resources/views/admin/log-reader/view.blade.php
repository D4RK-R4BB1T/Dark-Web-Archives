@extends('layouts.master')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-24">
                <div class="card">
                    <div class="card-header">Файл {{ $fileList[$id] }}</div>

                    <div class="card-body">
{{--                        @include('files.components.component-search')--}}
{{--                        @include('components.post-errors')--}}
                        <div class="row">
                            <div class="col-24">
                                <table class="table table-striped table-hover table-sm table-responsive">
                                    <thead>
                                        <tr>
                                            <th scope="col">Уровень</th>
                                            <th scope="col">Контекст</th>
                                            <th scope="col">Файл</th>
                                            <th scope="col">Дата</th>
                                            <th scope="col"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach ($reader as $entry)
                                        <tr id="log-{{ $entry->id }}" class="cursor-pointer log-line" data-id="{{ $entry->id }}">
                                            <th scope="row">
                                                @switch($entry->level)
                                                    @case('debug')
                                                        <span class="badge bg-default" id="level-{{ $entry->id }}">{{ $entry->level }}</span>
                                                    @break

                                                    @case('info')
                                                        <span class="badge bg-info" id="level-{{ $entry->id }}">{{ $entry->level }}</span>
                                                    @break

                                                    @case('warning')
                                                        <span class="badge bg-warning text-dark" id="level-{{ $entry->id }}">{{ $entry->level }}</span>
                                                    @break

                                                    @case('error')
                                                        <span class="badge bg-red" id="level-{{ $entry->id }}">{{ $entry->level }}</span>
                                                    @break
                                                @endswitch
                                            </th>
                                            <td class="small">
                                                <span class="d-inline-block text-truncate mw-1100" id="context-message-{{ $entry->id }}">{{ $entry->context->message }}</span>
                                            </td>
                                            <td class="small">
                                                {{ $entry->file_path }}:{{ $entry->context->line }}
                                            </td>
                                            <td id="date-{{ $entry->id }}" class="small">{{ $entry->date->format('d.m H:i:s') }}</td>
                                            <td>
                                                <textarea class="hidden" id="stack-{{ $entry->id }}">{!! $entry->stack_traces !!}</textarea>
                                                <input type="hidden" id="file-path-{{ $entry->id }}" value="{{ $entry->file_path }}">
                                                <input type="hidden" id="context-in-{{ $entry->id }}" value="{{ $entry->context->in }}">
                                                <input type="hidden" id="context-line-{{ $entry->id }}" value="{{ $entry->context->line }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div><!-- /.col-12 -->
                        </div><!-- /.row -->
                        <div class="row">
                            <div class="col-12">
                                {{ $reader->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
