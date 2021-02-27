@if (!empty($prev))
    <link rel="prev" href="{{ $prev }}">
@endif

@if (!empty($next))
    <link rel="next" href="{{ $next }}">
@endif

@if (!empty($canonical))
    <link rel="canonical" href="{{ $canonical }}">
@endif

@if ($noindex_follow)
    <meta name="robots" content="noindex, nofollow"/>
@endif

