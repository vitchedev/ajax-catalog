<?php

namespace Vaden\AjaxCatalog\Widgets;

class vueCascadeSingle extends Widgets
{
    public $active_links = 0;
    public $values = [];
    public $children = [];
    private $options = [];
    private $active = [];

    public function __construct($urlData, $widget_system_name, $widget_name)
    {
        $this->urlData = $urlData;
        $this->name = $widget_name;
        $this->system_name = $widget_system_name;
        //adopt values for our class
        foreach ($urlData->values[$widget_system_name] as $key => $value) {
            $value['alias'] = $this->getAlias($key);
            $this->values[$value['alias']] = $value;
            if ($value['parent']) {
                $this->children[$this->getAlias($value['parent'])][$value['alias']] = $value;
            }
        }
    }

    /*
     *returns array with data of the widget
    */
    public function getData()
    {
        //data about select links
        foreach ($this->getGroupedValues() as $value) {
            $this->options[] = $this->attachLink($value);
        }

        $links_arr = [
            'options' => $this->options,
            'active' => $this->attachActive(),
            'defaultUrl' => $this->active_links == 0 ? $this->urlData->originUrl : $this->getUrlWithoutActiveLinks(),
            'to_js' => true
        ];

        return $links_arr;

    }

    private function attachActive()
    {
        $active = [];

        if (!empty($this->active)) {
            $value = $this->values[$this->active['alias']];

            do {
                $old_value = $value;
                $active[] = $value['url'];
                if ($value['parent'] != null) {
                    $value = $this->values[$value['parent']];
                }
            } while ($old_value['parent'] != null);

            $active = array_reverse($active);
        }

        return $active;
    }

    /*
     *group our parents with children entities
    */
    private function getGroupedValues()
    {
        $grouped_values = [];

        foreach ($this->values as $value) {
            if ($value['parent'] == null) {
                $grouped_values[$value['alias']] = $this->attachChildValues($value);
            }
        }

        return $grouped_values;
    }

    private function attachChildValues($parent)
    {
        if (isset($this->children[$parent['alias']])) {
            $parent['children'] = $this->children[$parent['alias']];
            foreach ($parent['children'] as $key => $value) {
                $parent['children'][$value['alias']] = $this->attachChildValues($value);
            }
        }
        return $parent;
    }

    /*
     *returns array with data of the link
    */
    public function attachLink($value)
    {

        $state = $this->getState($value['value']);
        $params = $this->urlData->params;
        $linkParam = $this->urlData->paramFromAlias($value['alias']);

        //on click on not active item we should add or remove param from url
        $check_num = isset($this->urlData->settings['filters'][$this->system_name]['multiple_values']) ? $this->urlData->settings['filters'][$this->system_name]['multiple_values'] : false;
        if ($check_num) {
            $params = $this->urlData->addParam($params, $linkParam);
        } else {
            //remove from url all other vaues of our filter
            foreach ($this->values as $filter_value) {
                unset($params['filters'][$filter_value['alias']]);
            }
            //add to filter value of current link
            $params = $this->urlData->addParam($params, $linkParam);
        }

        $url = $this->urlData->urlGenerate($params);

        $option = [
            'label' => $value['name'],
            'value' => $url,
        ];

        $this->values[$value['alias']]['url'] = $url;

        if (isset($value['children'])) {
            foreach ($value['children'] as $sub_value) {
                $option['children'][] = $this->attachLink($sub_value);
            }
        }

        if ($state) {
            $this->active = $value;
        }

        return $option;

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
            if ($check_column == $act_value['0']) {
                if ($value == $act_value['2']) {
                    $state = true;
                    $this->active_links++;
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
            if ($this->system_name == $param[3]) {
                unset($params['filters'][$key]);
            }
        }

        return $this->urlData->urlGenerate($params);
    }

}