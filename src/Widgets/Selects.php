<?php

namespace Vaden\AjaxCatalog\Widgets;

class Selects extends Widgets
{
    public $active_links = 0;
    public $values = [];

    public function __construct($urlData, $widget_system_name, $widget_name)
    {
        $this->urlData = $urlData;
        $this->name = $widget_name;
        $this->system_name = $widget_system_name;
        foreach ($urlData->values[$widget_system_name] as $key => $value) {
            $value['alias'] = $this->getAlias($key);
            $this->values[$value['alias']] = $value;
        }
    }

    /*
     *returns array with data of the widget
    */
    public function getData()
    {
        $links_arr = [];

        //data about select links
        foreach ($this->values as $value) {
            $links_arr['options'][$value['value']] = $this->getLink($value);
        }

        //default select link
        $links_arr['default_url'] = $this->active_links == 0 ? $this->urlData->originUrl : $this->getUrlWithoutActiveLinks();

        return $links_arr;
    }

    /*
     *returns array with data of the link
    */
    public function getLink($value)
    {
        $state = $this->getState($value['value']);
        $params = $this->urlData->params;
        $linkParam = $this->urlData->paramFromAlias($value['alias']);

        if($state) {
            //on click on active item we should remove param from url
            $params = $this->urlData->removeParam($params, $linkParam);
        } else {
            //on click on not active item we should add or remove param from url
            $check_num = isset($this->urlData->settings['filters'][$this->system_name]['multiple_values']) ? $this->urlData->settings['filters'][$this->system_name]['multiple_values'] : false;
            if($check_num) {
                $params = $this->urlData->addParam($params, $linkParam);
            } else {
                //remove from url all other vaues of our filter
                foreach ($this->values as $filter_value) {
                    unset($params['filters'][$filter_value['alias']]);
                }
                //add to filter value of current link
                $params = $this->urlData->addParam($params, $linkParam);
            }
        }

        $link = [
            'name' => $value['name'],
            'url' => $this->urlData->urlGenerate($params),
            'state' => $state,
        ];
        //if we have deal with sub items
        if(isset($value['parent'])) {
            $link['parent'] = $value['parent'];
        }
        return $link;
    }

    /*
     *returns array with data of the widget
    */
    public function getState($value)
    {
        $state = false;
        $active_filters = $this->urlData->params['filters'];
        $check_column = $this->urlData->settings['filters'][$this->system_name]['table_column'];

        foreach ($active_filters as $act_key => $act_value) {
            if($check_column == $act_value['0']) {
                if($value == $act_value['2']) {
                    $state = true;
                    $this->active_links ++;
                }
            }
        }

        return $state;
    }

    /*
     *returns url withouts params of widget in it
    */
    public function getUrlWithoutActiveLinks()
    {
        $params = $this->urlData->params;

        foreach ($params['filters'] as $key => $param) {
            if($this->system_name == $param[3]) {
                unset($params['filters'][$key]);
            }
        }

        return $this->urlData->urlGenerate($params);
    }

}