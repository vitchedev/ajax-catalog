@if(!empty($widgetsData['currSearch']))
    <div class="row  bottom-buffer">
        <div class="col-sm-12">
            @foreach($widgetsData['currSearch'] as $link)
                @if($link['show'] === true)
                    <a href="{{ $link['url'] }}" title="{{ $link['name'] }}" class="btn btn-default ajax_link">
                        <span class="glyphicon glyphicon-remove icon-alert" aria-hidden="true"></span>
                        {{ $link['name'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif