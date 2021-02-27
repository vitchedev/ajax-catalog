<?php

namespace Vaden\AjaxCatalog;

use Illuminate\Database\Eloquent\Model;
use Yakim\FileUpload\FileUpload;
use Vaden\AjaxCatalog\Events\deleteAjaxCatalogPageEvent;

class AjaxCatalogPage extends Model
{
    protected $fillable = ['name', 'text', 'link', 'updated_at', 'created_at'];

    protected $dispatchesEvents = [
        'deleting' => deleteAjaxCatalogPageEvent::class,
    ];

    public function seo()
    {
        return $this->morphMany('Asmet\SeoModule\Models\Seo', 'seoable');
    }

    public function ajaxCatalogAdditionalFields()
    {
        $model = config('ajaxCatalog.additional_model')['model'];

        if (class_exists($model)) {
            return $this->hasOne($model, 'id', 'id');
        }
    }

    /**
     * // морфная связь с fileupload
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function fileUpload()
    {
        return $this->morphMany(FileUpload::class, 'fileable');
    }

}
