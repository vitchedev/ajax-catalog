<?php

namespace Vaden\AjaxCatalog\Listeners;

use Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Vaden\AjaxCatalog\Events\deleteAjaxCatalogPageEvent;

class deleteAjaxCatalogPageEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Handle the event.
     *
     * @param  object $event
     * @return void
     */
    public function handle(deleteAjaxCatalogPageEvent $event)
    {
        if ($seo = $event->page->seo->first()) {
            $seo->delete();
        }

        $model = config('ajaxCatalog.additional_model')['model'];
        if (class_exists($model)) {
            if ($ajaxCatalogAdditionalFields = $event->page->ajaxCatalogAdditionalFields) {
                $ajaxCatalogAdditionalFields->delete();
            }
        }
    }
}
