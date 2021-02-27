<?php

namespace Vaden\AjaxCatalog\Widgets;

class CurrentSearch extends Widgets
{
    public $links;

    public function __construct($urlData)
    {
        $this->links = $this->getLinks($urlData);
    }

    /*
     *returns array with links of the widget
    */
    public function getData()
    {
        $this->links;
        return $this->links;
    }

    /*
     *returns information about object links
    */
    private function getLinks($urlData)
    {
        $links = [];
        $shown_links = 0;
        $required_params = [];

        foreach ($urlData->params['filters'] as $filter_key => $filter) {
            $name = $urlData->settings['filters'][$filter['3']]['name'];
            $show = isset($urlData->settings['filters'][$filter['3']]['curr_search']) ? $urlData->settings['filters'][$filter['3']]['curr_search'] : true;

            if(isset($urlData->settings['filters'][$filter['3']]['required']) && $urlData->settings['filters'][$filter['3']]['required']) {
                $required_params[$filter_key] = $filter;
            }

            if(isset($urlData->values[$filter['3']])) {
                $filter_type = $urlData->settings['filters'][$filter['3']]['filter_type'];
                $alias = $this->getAliasBySysName($urlData, $filter_key, $filter_type);
                $name = $name  . ': ' . $urlData->values[$filter['3']][$alias]['name'];
            } else {
                $filter['2'] = str_replace('%', ' ', $filter['2']);
                $name = $name  . ': ' . $filter['2'];
            }

            $params = $urlData->params;
            unset($params['filters'][$filter_key]);
            $link = $urlData->urlGenerate($params);

            $links[] = [
                'name' => $name,
                'url' => $link,
                'show' => $show,
            ];

            if($show) {
                $shown_links++;
            }
        }

        if(!empty($urlData->params['filters']) && $shown_links > 0) {
            $params = [];
            $params['entity'] = $urlData->params['entity'];
            $params['filters'] = $required_params;
            $links[] = [
                'name' => 'Очистить все',
                'url' => $urlData->urlGenerate($params),
                'show' => true,
            ];
        }

        return $links;
    }

    /*
    *generates alias  from filters type and value
    */
    public function getAliasBySysName($urlData, $sys_alias, $filter_type) {
        switch ($filter_type) {
            case 'prefix':
                $alias = explode('-', $sys_alias);
                unset($alias[0]);
                $alias = implode('-', $alias);
                break;
            case 'name':
                $alias = $sys_alias;
                break;
        }

        return $alias;
    }

}