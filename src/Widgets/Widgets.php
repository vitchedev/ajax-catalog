<?php

namespace Vaden\AjaxCatalog\Widgets;

abstract class Widgets
{
    public $urlData;
    public $name;
    public $system_name;

    public function __construct($urlData, $widget_system_name, $widget_name)
    {
        $this->urlData = $urlData;
        $this->name = $widget_name;
        $this->system_name = $widget_system_name;
    }

    /*
     *returns data of widget
     */
    abstract public function getData();

    /*
    *generates alias  from filters type and value
    */
    public function getAlias($sys_alias) {
        $filter_type = $this->urlData->settings['filters'][$this->system_name]['filter_type'];
        switch ($filter_type) {
            case 'prefix':
                    $alias = $this->system_name . '-' . $sys_alias;
                break;
            case 'name':
                $alias = $sys_alias;
                break;
        }

        return $alias;
    }

    /*
    *generates alias  from filters type and value
    */
    public function sortByCount($links_arr, $config, $counters_data) {

        if ($config === 'exist_alphabet') {
            $temp_arr_count = [];
            $temp_arr_null = [];

            foreach ($links_arr as $key1 => $link) {
                $links_arr[$key1]['base_count'] = isset($counters_data[$key1]) ? $counters_data[$key1] : 0;
                if($links_arr[$key1]['base_count'] < $links_arr[$key1]['count']) {
                    $links_arr[$key1]['base_count'] = $links_arr[$key1]['count'];
                }
            }

            foreach ($links_arr as $key => $link) {
                if($link['base_count'] > 0) {
                    $temp_arr_count[$key] = $link['name'];
                } else {
                    $temp_arr_null[$key] = $link['name'];
                }
            }

            asort($temp_arr_count);
            asort($temp_arr_null);

            foreach ($temp_arr_count as $key => $link) {
                if (isset($links_arr[$key]['children'])) {
                    $links_arr[$key]['children'] = $this->sortByCount($links_arr[$key]['children'], $config, $counters_data);
                }
                $temp_arr_count[$key] = $links_arr[$key];
            }
            foreach ($temp_arr_null as $key => $link) {
                if (isset($links_arr[$key]['children'])) {
                    $links_arr[$key]['children'] = $this->sortByCount($links_arr[$key]['children'], $config, $counters_data);
                }
                $temp_arr_null[$key] = $links_arr[$key];
            }

            $links_arr = array_merge($temp_arr_count, $temp_arr_null);
        }
        elseif ($config === true) {
            $temp_arr = [];
            foreach ($links_arr as $key1 => $link) {
                $links_arr[$key1]['base_count'] = isset($counters_data[$key1]) ? $counters_data[$key1] : 0;
                if($links_arr[$key1]['base_count'] < $links_arr[$key1]['count']) {
                    $links_arr[$key1]['base_count'] = $links_arr[$key1]['count'];
                }
            }

            foreach ($links_arr as $key => $link) {
                $temp_arr[$key] = $link['base_count'];
            }

            arsort($temp_arr);

            foreach ($temp_arr as $key => $link) {
                if (isset($links_arr[$key]['children'])) {
                    $links_arr[$key]['children'] = $this->sortByCount($links_arr[$key]['children'], $config, $counters_data);
                }
                $temp_arr[$key] = $links_arr[$key];
            }

            $links_arr = $temp_arr;
        }

        return $links_arr;
    }
}