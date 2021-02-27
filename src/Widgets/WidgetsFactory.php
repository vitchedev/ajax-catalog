<?php
namespace Vaden\AjaxCatalog\Widgets;

class WidgetsFactory
{
    /**
     * create instanse of widget object
     */
    public static function create($urlData, $system_name, $name, $type)
    {
        $widget = null;
        switch ($type) {
            case 'links':
                $widget = new LinksList($urlData, $system_name, $name);
                break;
            case 'select':
                $widget = new Selects($urlData, $system_name, $name);
                break;
            case 'vueCascadeSingle':
                $widget = new vueCascadeSingle($urlData, $system_name, $name);
                break;
            case 'subCategories':
                $widget = new SubCategories($urlData, $system_name, $name);
                break;
            case 'categories':
                $widget = new Categories($urlData, $system_name, $name);
                break;
        }

        return $widget;
    }
}