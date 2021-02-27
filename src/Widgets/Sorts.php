<?php

namespace Vaden\AjaxCatalog\Widgets;

class Sorts extends Widgets
{
    public $urlData;

    public function __construct($urlData)
    {
        $this->urlData = $urlData;
    }

    /*
     *returns array with data of the widget
    */
    public function getData()
    {
        $links_arr = [];

        $sorts = isset($this->urlData->settings['sorts']) ? $this->urlData->settings['sorts'] : [];

        if(!empty($sorts)) {
            foreach ($sorts as $sort) {
                $sort_table = isset($sort['by_column']) ? $sort['by_column'] : $sort['by_scope'];
                $links_arr['sort'][$sort_table] = $this->getSortLink($sort_table, $sort);
            }

            //links for sort order
            $links_arr['order']['asc'] = $this->getOrderLink('asc');
            $links_arr['order']['desc'] = $this->getOrderLink('desc');
        }

        return $links_arr;
    }

    /*
     *returns array with data of the link
    */
    public function getSortLink($sort_table, $sort)
    {
        $state = $this->getSortState($sort_table);
        $params = $this->urlData->params;
        $linkParam = $this->urlData->paramFromAlias($sort['alias']);

        //on click on sort link we should remove all sorts from param and add one that have been clicked
        if (isset($params['sorts']['sort_item'])) {
            unset($params['sorts']['sort_item']);
        }
        $params = $this->urlData->addParam($params, $linkParam);

        $link = [
            'name' => $sort['name'],
            'url' => $this->urlData->urlGenerate($params),
            'state' => $state,
        ];
        return $link;
    }

    /*
     *returns array with data of the widget
    */
    public function getSortState($sort_table)
    {
        $state = false;
        //is sort link checked
        if (isset($this->urlData->params['sorts']['sort_item']['sort_by'])) {
            if ($this->urlData->params['sorts']['sort_item']['sort_by'] == $sort_table) {
                $state = true;
            }
        } elseif (isset($this->urlData->settings['default']['sorts']['value'])) {
            //is sort link checked by default
            if($this->urlData->settings['default']['sorts']['value'] == $sort_table) {
                $state = true;
            }
        }

        return $state;
    }

    /*
     *returns array with data of the link
    */
    public function getOrderLink($order)
    {
        $state = $this->getOrderState($order);
        $params = $this->urlData->params;
        if($order == 'asc') {
            $linkParam = $this->urlData->paramFromAlias('sa');
        }
        if($order == 'desc') {
            $linkParam = $this->urlData->paramFromAlias('sd');
        }

        //on click on sort link we should remove all sorts from param and add one that have been clicked
        if (isset($params['sorts']['sort_order'])) {
            unset($params['sorts']['sort_order']);
        }
        $params = $this->urlData->addParam($params, $linkParam);

        $link = [
            'name' => $order,
            'url' => $this->urlData->urlGenerate($params),
            'state' => $state,
        ];
        return $link;
    }

    /*
     *returns array with data of the widget
    */
    public function getOrderState($order)
    {
        $state = false;

        //is order link checked
        if (isset($this->urlData->params['sorts']['sort_order'])) {
            if ($this->urlData->params['sorts']['sort_order'] == $order) {
                $state = true;
            }
        } else {
            //is order link checked by default
            if(isset($this->urlData->settings['default']['sorts'])) {
                foreach ($this->urlData->settings['default']['sorts'] as $def_sort) {
                    if($def_sort == $order) {
                        $state = true;
                    }
                }
            }
        }

        return $state;
    }

}