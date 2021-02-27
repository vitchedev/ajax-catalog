<?php

namespace Vaden\AjaxCatalog\Widgets;

class Pagination extends Widgets
{
    public $info;
    public $links;
    public $items_count;
    public $query;

    public function __construct($urlData, $query, $items_count)
    {
        $pagination = $urlData->params['pagination'];
        $this->query = $query;
        $this->items_count = $items_count;
        $this->info = $this->getInfo($query, $pagination);
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
     *returns items only for current page
    */
    public function pageItems()
    {
        return $this->query->forPage($this->info['current_page'], $this->info['per_page'])->get();
    }

    /*
     *returns information about pager state
    */
    private function getInfo($query, $pagination)
    {
        $items_per_page = empty($pagination['per_page']) ? $pagination['base_page_size'] : $pagination['per_page'];
        $items_count = $this->items_count;

        $last_page = $items_count / $items_per_page;
        $last_page = ceil($last_page);
        $last_page = intval($last_page);

        $curr_page = empty($pagination['curr_page']) ? 1 : $pagination['curr_page'];

        $info = [
            'total' => $this->items_count,
            'last_page' => $last_page,
            'per_page' => $items_per_page,
            'current_page' => $curr_page,
        ];

        return $info;
    }

    /*
     *returns information about object links
    */
    private function getLinks($urlData)
    {
        $pagination_links = $this->getPaginationLinks($urlData);

        $params = $urlData->params;
        if (empty($params['pagination']['per_page'])) {
            $params['pagination']['per_page'] = $params['pagination']['base_page_size'];
        }

        $params['pagination']['per_page'] = $params['pagination']['per_page'] + $params['pagination']['base_page_size'];
        $params['pagination']['curr_page'] = 1;

        $links = [
            'show_more' => [
                'name' => __('ajaxCatalog.show_more'),
                'url' => $urlData->urlGenerate($params, true),
            ],
            'pagination' => $pagination_links,
        ];

        return $links;
    }

    /*
     *get pagination links array
    */
    private function getPaginationLinks($urlData)
    {
        $links = [];
        $curr_page = $this->info['current_page'];
        $last_page = $this->info['last_page'];

        if ($last_page <= 5) {
            //links if number of links is less then 5
            $i = 1;
            while ($i <= 5) {
                if ($i <= $last_page) {
                    if ($curr_page == $i) {
                        $links[] = [
                            'name' => $i,
                            'url' => '',
                            'type' => 'current',
                        ];
                    } else {
                        $params = $urlData->params;
                        $linkParam = $urlData->paramFromAlias('p-' . $i);
                        $params = $urlData->addParam($params, $linkParam);
                        $links[] = [
                            'name' => $i,
                            'url' => $urlData->urlGenerate($params, true),
                            'type' => 'link',
                        ];
                    }
                }
                $i++;
            }
        } else {
            //first link
            if ($curr_page == 1) {
                $links[] = [
                    'name' => 1,
                    'url' => '',
                    'type' => 'current',
                ];
            } else {
                $params = $urlData->params;
                $linkParam = $urlData->paramFromAlias('p-' . 1);
                $params = $urlData->addParam($params, $linkParam);
                $links[] = [
                    'name' => 1,
                    'url' => $urlData->urlGenerate($params, true),
                    'type' => 'link',
                ];
            }

            if ($curr_page == 1 || $curr_page == 2 || $curr_page == 3) {
                //we at the beginnig of the list
                $i = 2;
                while ($i <= 4) {
                    if ($curr_page == $i) {
                        $links[] = [
                            'name' => $i,
                            'url' => '',
                            'type' => 'current',
                        ];
                    } else {
                        $params = $urlData->params;
                        $linkParam = $urlData->paramFromAlias('p-' . $i);
                        $params = $urlData->addParam($params, $linkParam);
                        $links[] = [
                            'name' => $i,
                            'url' => $urlData->urlGenerate($params, true),
                            'type' => 'link',
                        ];
                    }
                    $i++;
                }

                $links[] = [
                    'name' => '...',
                    'url' => '',
                    'type' => 'deliminer',
                ];

            } elseif ($curr_page == $last_page || $curr_page == $last_page - 1 || $curr_page == $last_page - 2) {
                //we at the end of the list
                $links[] = [
                    'name' => '...',
                    'url' => '',
                    'type' => 'deliminer',
                ];

                $i = $last_page - 3;
                while ($i <= $last_page - 1) {
                    if ($curr_page == $i) {
                        $links[] = [
                            'name' => $i,
                            'url' => '',
                            'type' => 'current',
                        ];
                    } else {
                        $params = $urlData->params;
                        $linkParam = $urlData->paramFromAlias('p-' . $i);
                        $params = $urlData->addParam($params, $linkParam);
                        $links[] = [
                            'name' => $i,
                            'url' => $urlData->urlGenerate($params, true),
                            'type' => 'link',
                        ];
                    }
                    $i++;
                }
            } else {
                //we at center of the list
                $links[] = [
                    'name' => '...',
                    'url' => '',
                    'type' => 'deliminer',
                ];

                $i = $curr_page - 1;
                while ($i <= $curr_page + 1) {
                    if ($curr_page == $i) {
                        $links[] = [
                            'name' => $i,
                            'url' => '',
                            'type' => 'current',
                        ];
                    } else {
                        $params = $urlData->params;
                        $linkParam = $urlData->paramFromAlias('p-' . $i);
                        $params = $urlData->addParam($params, $linkParam);
                        $links[] = [
                            'name' => $i,
                            'url' => $urlData->urlGenerate($params, true),
                            'type' => 'link',
                        ];
                    }
                    $i++;
                }

                $links[] = [
                    'name' => '...',
                    'url' => '',
                    'type' => 'deliminer',
                ];
            }

            //last link
            if ($curr_page == $last_page) {
                $links[] = [
                    'name' => $last_page,
                    'url' => '',
                    'type' => 'current',
                ];
            } else {
                $params = $urlData->params;
                $linkParam = $urlData->paramFromAlias('p-' . $last_page);
                $params = $urlData->addParam($params, $linkParam);
                $links[] = [
                    'name' => $last_page,
                    'url' => $urlData->urlGenerate($params, true),
                    'type' => 'link',
                ];
            }
        }

        return $links;
    }
}