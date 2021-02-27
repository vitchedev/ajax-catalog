@if(!empty($widgetsData['sorts']))
    <div class="sorts btn-group" role="group">
        @foreach($widgetsData['sorts']['sort'] as $link)
            <a rel="nofollow" href="{{ $link['url'] }}" title="{{ $link['name'] }}"
               class="btn btn-default {{ $link['state'] ? 'active' : '' }} ajax_link">
                {{ $link['name'] }}
            </a>
        @endforeach
    </div>

    <div class="sorts btn-group" role="group">
        @foreach($widgetsData['sorts']['order'] as $link)
            <a rel="nofollow" href="{{ $link['url'] }}" title="{{ $link['name'] }}"
               class="btn btn-default {{ $link['state'] ? 'active' : '' }} ajax_link">
                    <span class="glyphicon {{ $link['name'] == 'asc' ? 'glyphicon-triangle-top' : 'glyphicon-triangle-bottom' }}"
                          aria-hidden="true"></span>
            </a>
        @endforeach
    </div>
@endif