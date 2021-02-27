<?php

namespace Vaden\AjaxCatalog;

use Cache;

class UrlConverter
{
    public $originUrl;
    //array of data from origin url
    public $params;
    //settings for current entity type
    public $settings;
    //data that should be passed to add info later
    public $for_add_info = [];
    //values for filters without prefixes
    public $values = [];
    public $origin_entity = [];

    private $short_url = false;
    private $short_entities = [];

    public function __construct($url)
    {
        //for russian letters
        $url = urldecode($url);
        $url = strtolower($url);
        $this->originUrl = $url;
        $this->params = $this->paramsGenerate($url);

    }

    /*
     *generate params from url if its possble
     */
    public function paramsGenerate($url)
    {
        $params = [
            'entity' => '',
            'model' => '',
            'filters' => [],
            'sorts' => [],
            'pagination' => [],
            'jq' => [
                'state' => false
            ],
        ];

        $url = explode('/', $url);
        $url = array_filter($url);

        //find entity to work with
        $entity = $this->validEntity($url);
        $this->origin_entity = $entity;

        if ($entity) {
            $params['entity'] = $entity['settings_key'];
            $params['model'] = $entity['model'];

            if (isset($entity['rout_group'])) {
                $params['rout_group'] = $entity['rout_group'];
            }

            //attach settings for our entity
            $this->settings = config('ajaxCatalog.' . $params['entity']);
            $this->settings['cache'] = isset($this->settings['cache']) ? $this->settings['cache'] : false;

            //cache url data
            $cache_tags = [config('app.name'), config('app.name') . 'ajax_catalog_url_converter'];
            $cache_key = $this->originUrl;
            if ($this->settings['cache']) {
                if ($cache = Cache::tags($cache_tags)->get($cache_key)) {
                    $this->settings = $cache['settings'];
                    $this->values = $cache['values'];
                    $this->for_add_info = $cache['for_add_info'];
                    return $cache['params'];
                } else {
                    //attach imported settings
                    $this->importFiltersToSettings();

                    //attach filter values for our entity
                    $this->attachPossibleValues($params, $url);

                    $params = $this->getNotCachedParams($params, $url);

                    $data = [
                        'settings' => $this->settings,
                        'values' => $this->values,
                        'for_add_info' => $this->for_add_info,
                        'params' => $params,
                    ];
                    Cache::tags($cache_tags)->put($cache_key, $data, 40);

                    return $params;
                }
            } else {
                //attach imported settings
                $this->importFiltersToSettings();
                //attach filter values for our entity
                $this->attachPossibleValues($params, $url);

                $params = $this->getNotCachedParams($params, $url);

                return $params;
            }
        }

        return false;
    }

    private function getNotCachedParams($params, $url)
    {
        //attach info about base pagination
        $params['pagination'] = [
            'base_page_size' => isset($this->settings['pagination']) ? $this->settings['pagination'] : 12,
            'per_page' => '',
            'curr_page' => '',
        ];

        //find values of other params in url
        foreach ($url as $url_param) {
            $param = $this->paramFromAlias($url_param);
            //check if this param exist in current catalog
            if ($param) {
                $params = $this->addParam($params, $param);
            } else {
                return false;
            }
        }

        if (!$this->isValidUrl($params)) {
            return false;
        }

        //attach default values to our params
        return $params;
    }

    /*
     * check function on 404 mistake
     */
    private function isValidUrl($params)
    {
        //check if this params passed in correct order
        $new_url = $this->urlGenerate($params, true);
        $check_url = $params['jq']['state'] ? $new_url . '/jq' : $new_url;
        $check_url = str_replace('/', '', $check_url);

        $origin_url = str_replace('/', '', $this->originUrl);
        //dd($origin_url . ' ' . $check_url);
        $check_url = strcmp($check_url, $origin_url);


        if ($check_url != 0) {
            return false;
        }

        //create counter to check single and require filters
        $counter = [];
        $required = [];
        $filters = isset($this->settings['filters']) ? $this->settings['filters'] : [];
        foreach ($filters as $url_prefix => $settings) {
            if (!isset($settings['multiple_values'])) {
                $counter[$url_prefix] = 0;
            }
            if (isset($settings['required']) && $settings['required'] == true) {
                $required[$url_prefix] = 0;
            }
        }

        //check that we don't have multiple single filters in our params
        foreach ($params['filters'] as $filter) {
            if (isset($counter[$filter['3']])) {
                $counter[$filter['3']]++;
                if ($counter[$filter['3']] > 1) {
                    return false;
                }
            }
            if (isset($required[$filter['3']])) {
                $required[$filter['3']]++;
            }
        }

        //check for required filters
        foreach ($required as $check) {
            if ($check == 0) {
                return false;
            }
        }

        return true;
    }

    /*
    *add new param in params array
    */
    public function addParam($params, $new_param)
    {
        $params[$new_param['type']][$new_param['key']] = $new_param['value'];
        return $params;
    }

    /*
    *remove param from params array
    */
    public function removeParam($params, $remove_param)
    {
        unset($params[$remove_param['type']][$remove_param['key']]);
        return $params;
    }

    /*
     *generate url from params array
    */
    public function urlGenerate($params, $with_pagination = false)
    {
        $aliases_arr = [];
        $sub_params = [];
        //separate params to normal arr
        foreach ($params as $group_key => $param_group) {
            $this->getParamsFromGroup($group_key, $param_group, $sub_params, $with_pagination);
        }

        //we sort on params because url aliases order depends of params type
        $sub_params = $this->paramsSort($sub_params);

        if ($this->short_url) {
            unset($sub_params['00-entity']);
            if (isset($this->origin_entity['rout_group'])) {
                $sub_params['00-route-group'] = ['type' => 'route-group', 'value' => $this->origin_entity['rout_group']];
                ksort($sub_params);
            }
        }

        foreach ($sub_params as $param) {
            $aliases_arr[] = $this->aliasFromParam($param);
        }

        //no trailing slash when using jq
        $aliases_arr = array_filter($aliases_arr);

        $url = '/' . implode('/', $aliases_arr);

        return $url;
    }

    /*
     *sort aliases to special order
    */
    public function paramsSort($sub_params)
    {

        foreach ($sub_params as $def_key => $param) {
            unset($sub_params[$def_key]);
            switch ($param['type']) {
                case 'entity':
                    //first in url should be entity type
                    $sorted_key = '00-entity';
                    break;
                case 'filters':
                    //after entity type goes filters in the order from settings
                    $weight_from_settings = 0;
                    foreach ($this->settings['filters'] as $settings_filter_key => $settings_filter) {
                        if ($param['value'][3] == $settings_filter_key) {
                            break;
                        }
                        $weight_from_settings++;
                    }
                    //prevent uncorrect sort by key
                    if (strlen($weight_from_settings) == 1) {
                        $weight_from_settings = '0' . $weight_from_settings;
                    }
                    //if params from one filter - bigger will be first
                    $val = $param['value']['2'];
                    if (is_array($val)) {
                        $val = implode('', $param['value']['2']);
                    }
                    $sorted_key = '30-filters-' . $weight_from_settings . '-' . $val;

                    $inner_weight = 0;
                    while (array_key_exists($sorted_key, $sub_params)) {
                        $inner_weight++;
                        $sorted_key = '30-filters-' . $weight_from_settings . '-' . $inner_weight;
                    }
                    //dd($sorted_key);

                    break;
                case 'sorts':
                    //after filters goes sorts
                    $sorted_key = '60-sorts-' . $param['key'];
                    break;
                case 'pagination':
                    //after sorts goes pagination
                    $sorted_key = '80-pagination-' . $param['key'];
                    break;
                case 'jq':
                    //the last one is jq marker
                    $sorted_key = '90-jq';
                    break;
            }

            $sub_params[$sorted_key] = $param;
        }

        ksort($sub_params);

        return $sub_params;
    }

    /*
     *returns array of common params from param group
    */
    public function getParamsFromGroup($group_key, $param_group, &$sub_params, $with_pagination)
    {
        switch ($group_key) {
            case 'entity':
                //param of an entity
                $sub_params[] = [
                    'type' => 'entity',
                    'key' => 'type',
                    'value' => $param_group,
                ];
                break;
            case 'jq':
                //param of an entity
                $param_group = array_filter($param_group);
                if (!empty($param_group)) {
                    $sub_params[] = [
                        'type' => 'jq',
                        'key' => 'state',
                        'value' => true,
                    ];
                }
                break;
            case 'filters':
                //params from filters
                foreach ($param_group as $type => $value) {
                    $sub_params[] = [
                        'type' => 'filters',
                        'key' => $type,
                        'value' => $value,
                    ];
                }
                break;
            case 'sorts':
                //params from sorts
                foreach ($param_group as $type => $value) {
                    $sub_params[] = [
                        'type' => 'sorts',
                        'key' => $type,
                        'value' => $value,
                    ];
                }
                break;
            case 'pagination':
                //params from pagination
                if ($with_pagination) {
                    foreach ($param_group as $type => $value) {
                        if ($type != 'base_page_size' && !empty($value)) {
                            $sub_params[] = [
                                'type' => 'pagination',
                                'key' => $type,
                                'value' => $value,
                            ];
                        }
                    }
                }
                break;
        }
    }

    /*
     *returns alias from param
    */
    public function aliasFromParam($param)
    {
        switch ($param['type']) {
            case 'entity':
                //entity alias
                $alias = $this->settings['alias'];
                break;
            case 'jq':
                //is it jquery link
                if ($param['value'] == true) {
                    $alias = '';
                }
                break;
            case 'filters':
                //param key is equal to its alias
                $alias = $param['key'];
                break;
            case 'sorts':
                if ($param['key'] == 'sort_item') {
                    $alias = $param['value']['alias'];
                }
                if ($param['key'] == 'sort_order') {
                    if ($param['value'] == 'asc') {
                        $alias = 'sa';
                    }
                    if ($param['value'] == 'desc') {
                        $alias = 'sd';
                    }
                }
                break;
            case 'pagination':
                if ($param['key'] == 'curr_page') {
                    $alias = 'p-' . $param['value'];
                }
                if ($param['key'] == 'per_page') {
                    $size = isset($this->settings['pagination']) ? $this->settings['pagination'] : 12;
                    $sm = round($param['value'] / $size);
                    $alias = 'sm-' . $sm;
                }
                break;
            case 'route-group':
                //entity alias
                $alias = $param['value'];
                break;
        }

        return $alias;
    }

    /*
     *check if alias is valid
     *returns array with meaning of alias
    */
    public function paramFromAlias($alias)
    {
        $param = false;

        if ($alias == 'jq') {
            //jquery for catalog is active
            $param = [
                'type' => 'jq',
                'key' => 'state',
                'value' => true,
            ];
        } else {
            //check for filters
            $param = $this->isAliasFilter($alias);
            if (!$param) {
                //check for sorts
                $param = $this->isAliasSort($alias);
                if (!$param) {
                    //check for pagination
                    $param = $this->isAliasPagination($alias);
                }
            }
        }

        return $param;
    }

    /*
     *check if alias is filter
     *returns array with meaning of alias
    */
    public function isAliasFilter($alias)
    {
        $param = false;

        $alias_arr = explode('-', $alias);

        //prepare values for filters with prefixes
        $prefix = $alias_arr[0];
        unset($alias_arr[0]);
        $prefix_value = implode('-', $alias_arr);

        //check that filter has value after prefix
        $filters = isset($this->settings['filters']) ? $this->settings['filters'] : [];
        foreach ($filters as $url_prefix => $settings) {
            if ($settings['filter_type'] == 'prefix' && $prefix == $url_prefix) {

                $search_for = $prefix_value;
                if ($settings['operator'] == 'in') {
                    if ($prefix_value == 0) {
                        $settings['operator'] = 'not in';
                    }
                    $search_for = $settings['in_values'];
                }

                $param = [
                    'type' => 'filters',
                    'key' => $alias,
                    //$url_prefix used to group filters to db or conditions
                    'value' => [$settings['table_column'], $settings['operator'], $search_for, $url_prefix],
                ];

            } elseif ($settings['filter_type'] == 'search' && $prefix == $url_prefix) {
                if ($settings['operator'] == 'like') {
                    $prefix_value = explode(' ', $prefix_value);
                    $prefix_value = implode('%', $prefix_value);
                    $search_for = '%' . $prefix_value . '%';
                } else {
                    $search_for = $prefix_value;
                }
                //$alias = mb_convert_encoding($alias, 'UTF-8');
                //$alias = utf8_encode_deep($alias);
                $param = [
                    'type' => 'filters',
                    'key' => $alias,
                    //$url_prefix used to group filters to db or conditions
                    'value' => [$settings['table_column'], $settings['operator'], $search_for, $url_prefix],
                ];

            } elseif ($settings['filter_type'] == 'date' && $prefix == $url_prefix) {
                $param = [
                    'type' => 'filters',
                    'key' => $alias,
                    //$url_prefix used to group filters to db or conditions
                    'value' => [$settings['table_column'], $settings['operator'], $prefix_value, $url_prefix],
                ];
            } elseif ($settings['filter_type'] == 'name') {
                //is current alias registered in filters?
                if (isset($this->values[$url_prefix][$alias])) {
                    $param = [
                        'type' => 'filters',
                        'key' => $alias,
                        //$url_prefix used to group filters to db or conditions
                        'value' => [$settings['table_column'], $settings['operator'], $this->values[$url_prefix][$alias]['value'], $url_prefix],
                    ];
                }
            }
        }

        return $param;
    }

    /*
     *check if alias is sorting
     *returns array with meaning of alias
    */
    public function isAliasSort($alias)
    {
        $param = false;

        switch ($alias) {
            case 'sa':
                //sort order - increase
                $param = [
                    'type' => 'sorts',
                    'key' => 'sort_order',
                    'value' => 'asc',
                ];
                break;
            case 'sd':
                //sort order - decrease
                $param = [
                    'type' => 'sorts',
                    'key' => 'sort_order',
                    'value' => 'desc',
                ];
                break;
            default:
                $sorts = isset($this->settings['sorts']) ? $this->settings['sorts'] : [];
                foreach ($sorts as $sort) {
                    if ($sort['alias'] == $alias) {
                        $sort_type = isset($sort['by_column']) ? 'by_column' : 'by_scope';
                        //sorts for spec entity
                        $param = [
                            'type' => 'sorts',
                            'key' => 'sort_item',
                            'value' => [
                                'sort_by' => $sort[$sort_type],
                                'type' => $sort_type,
                                'alias' => $alias
                            ],
                        ];
                    }
                }
        }

        return $param;
    }

    /*
     *check if alias is pagination
     *returns array with meaning of alias
    */
    public function isAliasPagination($alias)
    {
        $param = false;

        $alias = explode('-', $alias);

        if (count($alias == 2) && $alias[0] == 'p' && is_numeric($alias[1])) {
            $param = [
                'type' => 'pagination',
                'key' => 'curr_page',
                'value' => intval($alias[1]),
            ];
        }

        if (count($alias == 2) && $alias[0] == 'sm' && is_numeric($alias[1])) {
            $pag_num = isset($this->settings['pagination']) ? $this->settings['pagination'] : 12;
            $per_page = $pag_num * $alias[1];
            $param = [
                'type' => 'pagination',
                'key' => 'per_page',
                'value' => $per_page,
            ];
        }

        return $param;
    }

    /*
     *check if entity is valid
     * returns db table of valid entity
    */
    public function validEntity(&$entity)
    {
        $check = false;

        $entity = $this->checkShortUrl($entity);

        //block not short urls
        if (in_array($entity[0], $this->short_entities) && $this->short_url != true) {
            return false;
        }

        $valid_entities = config('ajaxCatalog.entities_list');

        foreach ($valid_entities as $value) {
            if (isset($value['rout_group'])) {

                $group = explode('/', $value['rout_group']);

                //route from controller will have url without group, but from ajax - with group
                $check_url_on_group = true;
                $i = 0;
                foreach ($group as $group_val) {
                    if ($group_val != $entity[$i]) {
                        $check_url_on_group = false;
                    }
                    $i++;
                }
                if ($check_url_on_group) {
                    if ($value['entity_alias'] == $entity[$i]) {
                        $check = $value;

                        //delete unnecessary params from url
                        while ($i >= 0) {
                            unset($entity[$i]);
                            $i--;
                        }
                        break;
                    }
                } else {
                    if ($value['entity_alias'] == $entity[0]) {
                        $check = $value;
                        unset($entity[0]);
                        break;
                    }
                }
            } else {
                if ($value['entity_alias'] == $entity[0]) {
                    $check = $value;
                    unset($entity[0]);
                    break;
                }
            }
        }

        return $check;
    }

    /*
     * process chort urls by urls
     */
    private function checkShortUrl($entity)
    {
        $short_urls_models = config('ajaxCatalog.short_urls');

        if ($short_urls_models) {
            $short_urls = [];
            //get urls from all models
            foreach ($short_urls_models as $model) {
                $short_urls = $short_urls + $model::ajaxCatalogShortUrls();
            }
            $short_entities = array_unique($short_urls);
            $this->short_entities = array_values($short_entities);

            if (!isset($entity[0])) {
                $entity = array_values($entity);
            }
            $route_groups = [];
            foreach (config('ajaxCatalog.entities_list') as $route_grope) {
                if (isset($route_grope['rout_group']) && $route_grope['rout_group']) {
                    $route_groups[$route_grope['rout_group']] = $route_grope['settings_key'];
                }
            }
            if (isset($entity[0]) && isset($short_urls[$entity[0]])) {
                array_unshift($entity, $short_urls[$entity[0]]);
                $this->short_url = true;
            } else {
                if (isset($entity[0]) && isset($route_groups[$entity[0]])) {
                    $group = $entity[0];
                    $group_alias = $entity[0] . '/' . $entity[1];
                    if (isset($entity[1]) && isset($short_urls[$group_alias])) {
                        unset($entity[0]);
                        array_unshift($entity, $short_urls[$group_alias]);
                        array_unshift($entity, $group);
                        $entity = array_values($entity);
                        $this->short_url = true;
                    }
                }
            }
        } else {
            if (!isset($entity[0])) {
                $entity = array_values($entity);
            }
        }

        return $entity;
    }

    /*
     * returns data about necessary values for filters
     */
    private function attachPossibleValues($params, $url_arr)
    {

        $filters = isset($this->settings['filters']) ? $this->settings['filters'] : [];

        foreach ($filters as $filter_key => $filter) {
            if (isset($filter['possible_values'])) {
                $settings_key = $this->origin_entity['settings_key'];
                if ($filter['possible_values'] == 'default') {
                    $this->values[$filter_key] = with(new $params['model'])->getAjaxCatalogPossibleValues($filter_key, $settings_key);
                } else {
                    if ($this->settings['filters'][$filter['possible_values']]['filter_type'] == 'name') {
                        $value_aliases = $this->values[$filter['possible_values']];
                        foreach ($url_arr as $alias) {
                            if (isset($value_aliases[$alias])) {
                                $this->values[$filter_key] = with(new $params['model'])->getAjaxCatalogPossibleValues($filter_key, $settings_key, $value_aliases[$alias]['value'], $filter['possible_values']);
                            }
                        }
                    } else {
                        foreach ($url_arr as $alias) {
                            $alias_arr = explode('-', $alias);
                            $prefix = $alias_arr[0];
                            unset($alias_arr[0]);
                            $value = implode('-', $alias_arr);

                            if ($prefix == $filter['possible_values']) {
                                $this->values[$filter_key] = with(new $params['model'])->getAjaxCatalogPossibleValues($filter_key, $settings_key, $value, $filter['possible_values']);
                            }
                        }
                    }
                }

            }
        }

    }

    private function importFiltersToSettings()
    {
        if (isset($this->settings['filters'])) {
            foreach ($this->settings['filters'] as $key => $filter) {
                if (isset($filter['import']['function'])) {
                    $func = $filter['import']['function'];

                    if (isset($filter['import']['param'])) {
                        $import = $func($filter['import']['param']);
                    } else {
                        $import = $func();
                    }

                    $filters_before = [];
                    $filters_after = [];
                    $check = true;
                    foreach ($this->settings['filters'] as $key_check => $filter_check) {
                        if ($check) {
                            if ($key_check == $key) {
                                $check = false;
                            } else {
                                $filters_before[$key_check] = $filter_check;
                            }
                        } else {
                            $filters_after[$key_check] = $filter_check;
                        }
                    }
                    $this->settings['filters'] = array_replace($filters_before, $import, $filters_after);

                    $for_add_info = [];
                    foreach ($import as $im_key => $val) {
                        $for_add_info[] = [
                            'name' => $im_key,
                            'readable_name' => $val['name']
                        ];
                    }
                    $this->for_add_info['attached_filters'][$key] = $for_add_info;
                }
            }
        }
    }
}
