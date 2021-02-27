@if($paginationInfo['last_page'] != 1)
    <div class="col-sm-12 bottom-buffer">
        <div class="btn-toolbar" role="toolbar">
            <div class="btn-group" role="group">
                @foreach($widgetsData['pagination']['pagination'] as $link)
                    @if($link['type'] == 'link')
                        <a href="{{ $link['url'] }}" title="{{ $link['name'] }}" class="btn btn-default ajax_link">
                            {{ $link['name'] }}
                        </a>
                    @elseif($link['type'] == 'current')
                        <span class="btn btn-default active">
                            {{ $link['name'] }}
                        </span>
                    @else
                        <span class="btn btn-default">
                            {{ $link['name'] }}
                        </span>
                    @endif
                @endforeach
            </div>
            @if($paginationInfo['last_page'] > $paginationInfo['current_page'])
                <div class="btn-group" role="group">
                    <a href="{{ $widgetsData['pagination']['show_more']['url'] }}"
                       title="{{ $widgetsData['pagination']['show_more']['name'] }}" class="btn btn-default ajax_link">
                        {{ $widgetsData['pagination']['show_more']['name'] }}
                    </a>
                </div>
            @endif
        </div>
    </div>
@endif