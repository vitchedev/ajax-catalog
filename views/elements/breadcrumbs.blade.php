@if(!empty($widgetsData['breadcrumbs']))
    <div id="breadcrumb" itemscope itemtype="http://schema.org/BreadcrumbList">
        <div class="breadcrumbs">
            @foreach($widgetsData['breadcrumbs'] as $key => $link)
                @if($link['widget_link'])
                    @if($key != 0)
                        <span class="delimiter">{{ $link['deliminer'] }}</span>
                    @endif
                    <span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                        <a itemprop="item" href="{{ $link['url'] }}" title="{{ $link['title'] }}" class="{{ $key != 0 ? 'ajax_link' : '' }}">
                             <span itemprop="name">{{ $link['ankor'] }}</span>
                        </a>
                        <meta itemprop="position" content="{{ $key + 1 }}">
                    </span>
                @else
                    <span class="delimiter">{{ $link['deliminer'] }}</span>
                    <span itemprop="itemListElement" itemscope itemtype="http://schema.org/ListItem">
                        <span itemprop="name">
                            {{ $link['ankor'] }}
                        </span>
                        <meta itemprop="position" content="{{ $key + 1 }}">
                    </span>
                @endif
            @endforeach
        </div>
    </div>
@endif
