<?php

namespace Vaden\AjaxCatalog;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as LServiceProvider;

class AjaxCatalogEventServiceProvider extends LServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'Vaden\AjaxCatalog\Events\deleteAjaxCatalogPageEvent' => [
            'Vaden\AjaxCatalog\Listeners\deleteAjaxCatalogPageEventListener'
        ],
    ];
}
