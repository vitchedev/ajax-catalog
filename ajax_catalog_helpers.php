<?php

use Vaden\AjaxCatalog\AjaxCatalogPage;

function page_meta_pagination($url, $curr_page, $last_page)
{
    $real_url = '/' . $url;
    $has_page = preg_match("/\/p-[0-9]+$/", $url);
    $url = preg_replace("/\/p-[0-9]+/", "", urldecode($url));
    $url = '/' . $url;

    $open_to_index = AjaxCatalogPage::where('link', $real_url)->exists();
    
    $next = null;
    $prev = null;
    $canonical = null;
    $noindex_follow = false;
    if ($open_to_index) {
        if ($curr_page == 1 && $last_page != 1) {
            $next = $url . '/p-' . ($curr_page + 1);
        } elseif ($curr_page == $last_page && $last_page != 1) {
            $prev = $curr_page == 2 ? $url : $url . '/p-' . ($curr_page - 1);
        } elseif ($last_page != 1) {
            $prev = $curr_page == 2 ? $url : $url . '/p-' . ($curr_page - 1);
            $next = $url . '/p-' . ($curr_page + 1);
        }
//        if ($has_page && $curr_page == 1) {
//            $canonical = $url;
//        }
    } else {
        $noindex_follow = true;
    }
    return view('ajaxCatalogElements::pagination-meta',
        compact(['prev', 'next', 'canonical', 'noindex_follow']))->render();
}