@extends($jq ? 'ajaxCatalogElements::ajaxLayout' : $master_blade)

@section('title', __('ajaxCatalog.catalog_admin_h1'))

@section('pagination_meta')
    {!! page_meta_pagination(Request::path(), $paginationInfo['current_page'], $paginationInfo['last_page']) !!}
@endsection

@section('content')
    <div id="catalog_page_ajax">

        <div id="breadcrumbs_load_zone">
            @include('ajaxCatalogElements::breadcrumbs')
        </div>

        <div id="filters_load_zone">

            <a href="{{ route('ajaxCatalogPage.create') }}" class="btn btn-info float-right" title="@lang('ajaxCatalog.create_page')">
                <span class="glyphicon glyphicon-floppy-open" aria-hidden="true"></span>
                @lang('ajaxCatalog.create_page')
            </a>

            <h1>@lang('ajaxCatalog.catalog_pages_title') (@lang('ajaxCatalog.find') {{ $itemsCount }})</h1>

            <hr>

            <form method="POST" action="{{ route('catalogAjaxField') }}" class="form-horizontal" id="form_catalog"
                  enctype="multipart/form-data">

                {{ csrf_field() }}

                <div class="row" style="margin-bottom: 15px;">
                    <div class="col-sm-2">
                        <input class="autocompleteInput2 form-control ajax_filter" type="text"
                               placeholder="@lang('ajaxCatalog.page_name')"
                               data-type="text" data-table="ajax_catalog_pages" data-column="name" name="name"
                               autocomplete="off"
                               value="{{ empty($widgetsData['currTextValues']['name']) ? '' : $widgetsData['currTextValues']['name'] }}">
                    </div>
                    <div class="col-sm-1">
                        @lang('ajaxCatalog.creation_date'):
                    </div>
                    <div class="col-sm-2">
                        <div class='input-group date ajaxDate2 ajax_filter_date'
                             data-default="{{ empty($widgetsData['currTextValues']['cab']) ? '' : $widgetsData['currTextValues']['cab'] }}">
                            <input type='text' class="form-control" placeholder="@lang('ajaxCatalog.from')" name="cab"
                                   value="{{ empty($widgetsData['currTextValues']['cab']) ? '' : $widgetsData['currTextValues']['cab'] }}"/>
                            <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class='input-group date ajaxDate2 ajax_filter_date'
                             data-default="{{ empty($widgetsData['currTextValues']['cae']) ? '' : $widgetsData['currTextValues']['cae'] }}">
                            <input type='text' class="form-control" placeholder="@lang('ajaxCatalog.to')" name="cae"
                                   value="{{ empty($widgetsData['currTextValues']['cae']) ? '' : $widgetsData['currTextValues']['cae'] }}"/>
                            <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                        </div>
                    </div>

                    <div class="col-sm-1">
                        @lang('ajaxCatalog.update_date'):
                    </div>
                    <div class="col-sm-2">
                        <div class='input-group date ajaxDate2 ajax_filter_date'
                             data-default="{{ empty($widgetsData['currTextValues']['uab']) ? '' : $widgetsData['currTextValues']['uab'] }}">
                            <input type='text' class="form-control" placeholder="@lang('ajaxCatalog.from')" name="uab"
                                   value="{{ empty($widgetsData['currTextValues']['uab']) ? '' : $widgetsData['currTextValues']['uab'] }}"/>
                            <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <div class='input-group date ajaxDate2 ajax_filter_date'
                             data-default="{{ empty($widgetsData['currTextValues']['uae']) ? '' : $widgetsData['currTextValues']['uae'] }}">
                            <input type='text' class="form-control" placeholder="@lang('ajaxCatalog.to')" name="uae"
                                   value="{{ empty($widgetsData['currTextValues']['uae']) ? '' : $widgetsData['currTextValues']['uae'] }}"/>
                            <span class="input-group-addon">
                        <span class="glyphicon glyphicon-calendar"></span>
                    </span>
                        </div>
                    </div>
                </div>

            </form>
        </div>

        <div class="btn-toolbar" style="margin-bottom: 15px;">

            @include('ajaxCatalogElements::exportForm')

            <div id="sorts_load_zone">
                @include('ajaxCatalogElements::sorts')
            </div>

        </div>

        <div id="curr_search_load_zone">
            @include('ajaxCatalogElements::currSearch')
        </div>

        <div id="items_load_zone">
            @if(isset($itemsData[0]->id))
                @if(!empty($bulk_operations))
                    <form method="POST" action="{{ route('catalogBulkOperations') }}" class="form-horizontal"
                          enctype="multipart/form-data" id="search_bulk_operations">
                        {{ csrf_field() }}
                        <input type="hidden" name="requestUrl" value="{{ Request::path() }}">
                        @endif

                        <ul class="items list-group">
                            @include('ajaxCatalogElements::bulkOperationsFields')

                            @foreach ($itemsData as $item)
                                <li class="list-group-item" id="search_item_{{ $item->id }}">
                                    <div class="row">

                                        <div class="col-sm-1">
                                            @if(!empty($bulk_operations))
                                                <input type="checkbox" name="item_{{ $item->id }}"
                                                       value="{{ $item->id }}"
                                                       class="search_bulk_trigger" style="margin-right: 15px;">
                                            @endif

                                            #{{ $item->id }}
                                        </div>

                                        <div class="col-sm-6">
                                            <a href="{{ $item->link }}" class="link" title="@lang('ajaxCatalog.go_to_page')">{{ $item->name }}</a>
                                        </div>
                                        <div class="col-sm-2">
                                            {{ $item->created_at }}<br>
                                            {{ $item->updated_at }}
                                        </div>
                                        <div class="col-sm-3">
                                            <a href="{{ route('ajaxCatalogPage.edit', $item->id) }}" class="btn btn-default btn-xs edit" title="@lang('ajaxCatalog.go_to_edit')" style="margin-right: 15px;">
                                                <span class="glyphicon glyphicon-edit icon-success" aria-hidden="true"></span>
                                                @lang('ajaxCatalog.change')
                                            </a>
                                            <a href="{{ route('ajaxCatalogPage.delete', $item->id) }}" class="btn btn-default btn-xs" title="Удалить страницу" onclick="if( ! confirm('{{ __('ajaxCatalog.delete_confirmation') }}')){return false;}">
                                                <span class="glyphicon glyphicon-remove icon-alert" aria-hidden="true"></span>
                                                @lang('ajaxCatalog.delete')
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                        @include('ajaxCatalogElements::pagination')

                        @if(!empty($bulk_operations))
                    </form>
                @endif
            @else
                <div class="alert alert-danger" role="alert">
                    @lang('ajaxCatalog.empty')
                </div>
            @endif
        </div>

    </div>
@endsection

@section('scripts')
    <script src="{{ asset('js/ajaxcatalog.js') }}"></script>
@endsection