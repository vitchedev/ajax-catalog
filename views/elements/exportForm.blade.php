@if(!empty($export_fields))
    <form method="POST" action="#" class="form-horizontal top-buffer"
          enctype="multipart/form-data">

        {{ csrf_field() }}

        <input type="hidden" name="requestUrl" value="{{ Request::path() }}">

        <div class="export btn-group">
            <button class="btn btn-default dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown"
                    aria-haspopup="true" aria-expanded="true">
                @lang('ajaxCatalog.export')
                <span class="caret"></span>
            </button>

            <ul class="dropdown-menu  form-dropdown" aria-labelledby="dropdownMenu1">

                @foreach ($export_fields as $item_key => $item_field)
                    <li>
                        <label>
                            <input type="checkbox" name="{{ $item_key }}"
                                   value="{{ $item_key }}"
                                   {{ $item_field['checked'] ? 'checked' : '' }} class="search_export_trigger">
                            {{ $item_field['name'] }}
                        </label>
                    </li>
                @endforeach
                <li role="separator" class="divider"></li>
                <li>
                    <label class="showAll">
                        <input type="checkbox" name="check_all" value="check_all" id="search_export">
                        @lang('ajaxCatalog.export_all')
                    </label>
                </li>
                <li>
                    <button class="btn btn-info" type="submit">
                        <span class="glyphicon glyphicon-share" aria-hidden="true"></span>
                        @lang('ajaxCatalog.exports')
                    </button>
                </li>
            </ul>
        </div>
    </form>
@endif
