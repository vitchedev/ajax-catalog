<?php

namespace Vaden\AjaxCatalog\Widgets;

class CurrentTextFieldValues extends Widgets
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

        foreach ($urlData->params['filters'] as $filter_key => $filter) {
            if($urlData->settings['filters'][$filter['3']]['filter_type'] == 'search' || $urlData->settings['filters'][$filter['3']]['filter_type'] == 'date') {
                $value = trim(str_replace('%', ' ', $filter['2']));
                //pass data about values of the filters by its system name
                $links[$filter['3']] = $value;
            }
        }

        return $links;
    }

}