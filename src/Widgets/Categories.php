<?php

namespace Vaden\AjaxCatalog\Widgets;

use Vaden\AjaxCatalog\AjaxCatalogPage;
use Vaden\AjaxCatalog\UrlConverter;
use Vaden\AjaxCatalog\ResultsGenerator;

class Categories extends Widgets
{
    private $active_links = [];
    private $active_children = [];
    private $active_parents = [];
    public $values = [];
    private $show_counter = false;
    private $order_by_counter = false;
    private $counters_data = [];
    public $children = [];

    public function __construct($urlData, $widget_system_name, $widget_name)
    {
        $this->urlData = $urlData;
        $this->name = $widget_name;
        $this->system_name = $widget_system_name;
        foreach ($urlData->values[$widget_system_name] as $key => $value) {
            $value['alias'] = $this->getAlias($key);
            $this->values[$value['alias']] = $value;
            if ($value['parent']) {
                $this->children[$this->getAlias($value['parent'])][$value['alias']] = $value;
            }
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
        foreach ($this->getGroupedValues() as $value) {
            $links_arr[$value['value']] = $this->getLink($value);
        }


        if ($this->order_by_counter) {
            $links_arr = $this->sortByCount($links_arr, $this->order_by_counter, $this->counters_data);
        }

        $links_arr = $this->addNoFollow($links_arr);

        return $links_arr;
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

                $params = $this->removeRelatedParams($params, $value);

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

        if (isset($value['children'])) {
            foreach ($value['children'] as $sub_key => $sub_value) {
                $link['children'][$sub_key] = $this->getLink($sub_value);
            }
        }

        if (isset($this->active_children[$value['value']])) {
            $link['active_child'] = true;
        } else {
            $link['active_child'] = false;

        }

        if (isset($this->active_parents[$value['value']])) {
            $link['active_parent'] = true;
        } else {
            $link['active_parent'] = false;
        }

        //add count after all statuses
        if ($this->show_counter) {
            $link['count'] = $this->getCount($link, $value['value']);
        }
        return $link;
    }

    /*
     *remove unnecessary params from our links
    */
    private function removeRelatedParams($params, $value)
    {
        foreach ($params['filters'] as $key => $param) {
            if ($param[3] == $this->system_name) {

                foreach ($this->getAllParentValues($value) as $parent) {
                    if ($parent == $param[2]) {
                        unset($params['filters'][$key]);
                    }
                }

                foreach ($this->getAllChildrenValues($value) as $child) {
                    if ($child == $param[2]) {
                        unset($params['filters'][$key]);
                    }
                }

            }
        }

        return $params;
    }

    /*
     * returns all parents on all levels for value
     */
    private function getAllParentValues($value)
    {
        $parents = [];

        $parents = $this->addParentToArr($parents, $value);

        return $parents;
    }

    private function addParentToArr($parents, $value)
    {
        if (isset($value['parent']) && $value['parent'] != null) {
            $parents[] = $value['parent'];
            $value = $this->values[$this->getAlias($value['parent'])];
            $parents = $this->addParentToArr($parents, $value);
        }
        return $parents;
    }

    /*
     * returns children on all levels for value
     */
    private function getAllChildrenValues($value)
    {
        $children = [];

        $children = $this->addChildrenToArr($children, $value);

        return $children;
    }

    private function addChildrenToArr($children, $value)
    {
        if (isset($this->children[$this->getAlias($value['value'])])) {
            foreach ($this->children[$this->getAlias($value['value'])] as $child) {
                $children[] = $child['value'];
                $children = $this->addChildrenToArr($children, $child);
            }
        }
        return $children;
    }

    /*
     *returns quantity of items in the filter
    */
    private function getCount($link, $value)
    {
        $filterCount = isset($this->counters_data[$value]) ? $this->counters_data[$value] : 0;

        if (count($this->active_links) == 0) {
            $result = $filterCount;
        } elseif ($link['active_parent']) {
            $children_count = 0;
            foreach ($link['children'] as $child) {
                if ($child['state']) {
                    $children_count += $child['count'];
                }
            }
            $result = $filterCount - $children_count;
            $result = $result > 0 ? '+' . $result : 0;
        } elseif ($link['active_child']) {
            $result = $filterCount;
        } elseif ($link['state']) {
            $result = $filterCount;
        } else {
            $result = $filterCount > 0 ? '+' . $filterCount : 0;
        }


        return $result;
    }

    /*
     *returns array with data of the widget
    */
    public function getState($value)
    {
        $state = false;

        if (isset($this->active_links[$value])) {
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
                $active_links[$act_value['2']] = '';
                $value = $this->values[$this->getAlias($act_value['2'])];
                $parents = array_flip($this->getAllParentValues($value));
                $this->active_parents += $parents;
                $children = array_flip($this->getAllChildrenValues($value));
                $this->active_children += $children;
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

        $links = $this->addRecursiveNoFollow($links, $isset_links);

        return $links;
    }

    /*
     *adds nofollow recursive to generatet links
     *
     * @param array $links, array $isset_links
     *
     * @return array
    */
    private function addRecursiveNoFollow($links, $isset_links)
    {
        foreach ($links as $key => $link) {
            $links[$key]['nofollow'] = false;
            if (in_array($link['url'], $isset_links)) {
                $links[$key]['nofollow'] = true;
            }
            if (isset($links[$key]['children'])) {
                $links[$key]['children'] = $this->addRecursiveNoFollow($links[$key]['children'], $isset_links);
            }
        }

        return $links;
    }
}