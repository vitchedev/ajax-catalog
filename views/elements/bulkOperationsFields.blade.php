@if(!empty($bulk_operations))
    <li class="list-group-item">
        <div class="row">
            <div class="col-sm-3">
                <label class="showAll font-weight-normal">
                    <input type="checkbox" name="check_all" value="check_all" id="search_bulk">
                    @lang('ajaxCatalog.bulk_all')
                </label>
            </div>

            <div class="col-sm-2">
                <b>@lang('ajaxCatalog.bulk_checked')</b>
            </div>

            <div class="col-sm-5">
                <select class="form-control" name="operation">
                    @foreach($bulk_operations as $operation_key => $operation_value)
                        @if($operation_value['gate'] === false || Gate::allows($operation_value['gate']))
                            <option class="{{$operation_value['check'] ? 'check' : 'no_check' }} {{$operation_value['type'] == 'link' ? 'by_js' : '' }}"
                                    value="{{ $operation_key }}">{{ $operation_value['name'] }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="col-sm-2">
                <button class="btn btn-default" type="submit">
                    <span class="glyphicon glyphicon-fast-forward" aria-hidden="true"></span>
                    @lang('ajaxCatalog.bulk_process')
                </button>
            </div>
        </div>
    </li>
@endif