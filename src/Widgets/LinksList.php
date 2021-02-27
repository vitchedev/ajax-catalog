<?php

namespace Vaden\AjaxCatalog\Widgets;

use Vaden\AjaxCatalog\AjaxCatalogPage;
use Vaden\AjaxCatalog\UrlConverter;
use Vaden\AjaxCatalog\ResultsGenerator;

class LinksList extends Widgets
{
    public $active_links = [];
    public $values = [];
    private $show_counter = false;
    private $order_by_counter = false;
    private $counters_data = [];

    public function __construct($urlData, $widget_system_name, $widget_name)
    {
        $this->urlData = $urlData;
        $this->name = $widget_name;
        $this->system_name = $widget_system_name;
        foreach ($urlData->values[$widget_system_name] as $key => $value) {
            $value['alias'] = $this->getAlias($key);
            $this->values[$value['alias']] = $value;
        }

        $this->show_counter = isset($this->urlData->settings['filters'][$this->system_name]['show_counter']) ? $this->urlData->settings['filters'][$this->system_name]['show_counter'] : false;
        $this->order_by_counter = isset($this->urlData->settings['filters'][$this->system_name]['order_by_counter']) ? $this->urlData->settings['filters'][$this->system_name]['order_by_counter'] : false;
        $this->active_links = $this->getActiveLinks();
        if ($this->show_counter) {
            $this->counters_data = $this->getCountersData();
        }
    }

    /*
     *returns array with data of the widget
    */
    public function getData()
    {
        $links_arr = [];
        foreach ($this->values as $value) {
            $links_arr[$value['value']] = $this->getLink($value);
        }

        if ($this->order_by_counter) {
            $links_arr = $this->sortByCount($links_arr, $this->order_by_counter, $this->counters_data);
        }

        $links_arr = $this->addNoFollow($links_arr);

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

        if ($state) {
            //on click on active item we should remove param from url
            $params = $this->urlData->removeParam($params, $linkParam);
        } else {
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
        }

        $url = $this->urlData->urlGenerate($params);
        $link = [
            'name' => $value['name'],
            'url' => $url,
            'state' => $state,
        ];
        //if we have deal with sub items
        if (isset($value['parent'])) {
            $link['parent'] = $value['parent'];
        }

        if ($this->show_counter) {
            $link['count'] = $this->getCount($state, $value['value']);
        }

        return $link;
    }

    /*
     *returns quantity of items in the filter
    */
    private function getCount($state, $value)
    {
        $filterCount = isset($this->counters_data[$value]) ? $this->counters_data[$value] : 0;

        if (count($this->active_links) == 0) {
            $result = $filterCount;
        } else {
            if ($state) {
                $result = $filterCount;
            } else {
                $result = $filterCount > 0 ? '+' . $filterCount : 0;
            }
        }

        return $result;
    }

    /*
     *returns array with data of the widget
    */
    public function getState($value)
    {
        $state = false;

        $val = $value;
        if (is_array($val)) {
            $val = implode('', $value);
        }

        if (isset($this->active_links[$val])) {
            $state = true;
        }

        return $state;
    }

    /*
     * finds active links
     */
    private function getActiveLinks()
    {
        $active_links = [];

        foreach ($this->urlData->params['filters'] as $act_key => $act_value) {
            if ($this->system_name == $act_value['3']) {
                $val = $act_value['2'];
                if ($act_value[1] == 'in') {
                    $val = 1;
                } elseif ($act_value[1] == 'not in') {
                    $val = 0;
                }
                $active_links[$val] = '';
            }
        }

        return $active_links;
    }

    /*
     * get data about items links
     */
    private function getCountersData()
    {

        $urlData = new UrlConverter($this->getUrlWithoutActiveLinks());

        return with(new ResultsGenerator($urlData))->getFilterCounts($this->system_name);
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

    /*
     *adds nofollow to generatet links
     *
     * @param array $links
     *
     * @return array
    */
    private function addNoFollow($links)
    {
        $links_query = [];

        foreach ($links as $link) {
            $links_query[] = $link['url'];
        }

        $isset_links = AjaxCatalogPage::whereIn('link', $links_query)
            ->get()
            ->pluck('link')
            ->toArray();

        foreach ($links as $key => $link) {
            $links[$key]['nofollow'] = false;
            if (in_array($link['url'], $isset_links)) {
                $links[$key]['nofollow'] = true;
            }
        }

        return $links;
    }

}