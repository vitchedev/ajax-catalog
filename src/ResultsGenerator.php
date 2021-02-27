<?php

namespace Vaden\AjaxCatalog;

use Behat\Transliterator\Transliterator;

class ResultsGenerator
{
    private $urlData;
    public $items_count;
    public $filters_add_info = [];

    public function __construct(UrlConverter $urlData)
    {
        $this->urlData = $urlData;
    }

    /*
     *returns collection of items for current page
     * we can get not all, just necessary rows from tables using $rows_for_selection param
     */
    public function getData($items_for_selection = [])
    {

        $model = new $this->urlData->params['model'];

        $query = $model->with(isset($this->urlData->settings['relationships']) ? $this->urlData->settings['relationships'] : []);

        $query2 = clone $query;

        $this->addFilters($query, $items_for_selection);

        $count_by = $this->urlData->settings['count_by'] ?? null;

        $count_query = clone $query;

        if ($count_by) {
            $this->items_count = \DB::query()->selectRaw(
                sprintf(
                    'COUNT(*) count FROM (%s) agg',
                    $count_query->select($count_by)->toSql()
                ),
                $count_query->getBindings()
            )->value('count');
        } else {
            $this->items_count = $count_query->count();
        }

        $this->addSorts($query);

        //query for add info have spec settings
        $this->addFilters($query2, $items_for_selection, true);
        $this->attachAddInfo($query2);

        return $query;
    }

    /*
     *returns array with counted filter items
     */
    public function getFilterCounts($filter_name)
    {

        $model = new $this->urlData->params['model'];

        $query = $model->with(isset($this->urlData->settings['relationships']) ? $this->urlData->settings['relationships'] : []);

        $this->addFilters($query);

        $column = $this->getBaseColumnName($this->urlData->settings['filters'][$filter_name]['table_column']);

        $valuesCount = [];

        $filterRelation = $this->checkFilterRelation($filter_name);
        $filterScope = $this->checkFilterScope($filter_name);

        if ($filterRelation) {
            if (method_exists($model->$filterRelation(), 'getTable')) {
                $relatedTable = $model->$filterRelation()->getTable();
            } else {
                $relatedTable = $model->$filterRelation()->getRelated()->getTable();
            }
            $relatedColumn = $this->urlData->settings['filters'][$filter_name]['related_column'];
            $groupColumn = $this->urlData->settings['filters'][$filter_name]['group_column'];
            $baseColumn = $this->urlData->settings['filters'][$filter_name]['base_column'];
            $baseTable = $model->getTable();

            $results = $query->join($relatedTable, $relatedTable . '.' . $relatedColumn, '=', $baseTable . '.' . $baseColumn)
                ->select($relatedTable . '.' . $groupColumn . ' as value', \DB::raw('count(' . $relatedTable . '.' . $groupColumn . ') as count'))
                ->groupBy($relatedTable . '.' . $groupColumn)
                ->get();

        } elseif ($filterScope) {
            $scope = $this->urlData->settings['filters'][$filter_name]['counter_scope'];

            $results = $query->$scope()->get();

        } else {
            $results = $query->groupBy($column)->get([$column . ' as value', \DB::raw('count(' . $column . ') as count')]);
        }


        foreach ($results as $result) {
            $valuesCount[$result->value] = $result->count;
        };

        //dd($valuesCount);
        return $valuesCount;
    }

    private function attachAddInfo($query)
    {
        if (isset($this->urlData->settings['filters'])) {
            foreach ($this->urlData->settings['filters'] as $key => $filter) {
                if (isset($filter['add_info'])) {
                    $scope = $filter['add_info'];
                    $result = $query->$scope($query);
                    if (is_a($result, 'Illuminate\Database\Eloquent\Builder')) {
                        $this->filters_add_info[$key] = null;
                    } else {
                        $this->filters_add_info[$key] = $result;
                    }
                }
            }
        }
    }

    /*
     *add filters to our query
     */
    private function addFilters($query, $items_for_selection = [], $without_add_info = false)
    {
        $def_filters = isset($this->urlData->settings['default']['filters']) ? $this->urlData->settings['default']['filters'] : [];

        $params = $this->addDefaultFiltersToParams($this->urlData->params, $def_filters);

        foreach ($def_filters as $def_filter) {
            if (isset($def_filter['by_scope'])) {
                $filterScope = $def_filter['by_scope'];
                $query = $query->$filterScope($def_filter);
            }
        }

        $grouped_filters = $this->groupFilters($params);

        if (!empty($items_for_selection)) {
            $query = $query->whereIn('id', $items_for_selection);
        }

        foreach ($grouped_filters['single'] as $filter) {
            $filterRelation = $this->checkFilterRelation($filter[3]);
            $filterScope = $this->checkFilterScope($filter[3]);

            if ($without_add_info) {
                if (isset($this->urlData->settings['filters'][$filter[3]]['not_in_add_info']) && $this->urlData->settings['filters'][$filter[3]]['not_in_add_info'] === true) {
                    continue;
                }
            }

            if ($filterRelation) {
                $query = $query->whereHas($filterRelation, function ($q) use ($filter, $filterRelation) {
                    $this->addWhere($q, $this->getBaseColumnName($filter[0], $filterRelation), $filter[1], $filter[2]);
                });
            } elseif ($filterScope) {
                $query = $query->$filterScope($filter[0], $filter[1], $filter[2]);
            } else {
                $query = $this->addWhere($query, $this->getBaseColumnName($filter[0]), $filter[1], $filter[2]);
            }
        }

        foreach ($grouped_filters['group']['group_marks'] as $group_key => $filter_names) {

            $filterRelation = $this->checkFilterRelation($grouped_filters['group'][$filter_names[0]][3]);
            $filterScope = $this->checkFilterScope($grouped_filters['group'][$filter_names[0]][3]);

            if ($filterRelation) {
                $query = $query->whereHas($filterRelation,
                    function ($q) use ($filter_names, $grouped_filters, $filterRelation) {
                        $q->where(function ($query) use ($filter_names, $grouped_filters, $filterRelation) {
                            foreach ($filter_names as $filter_name) {
                                $filter = $grouped_filters['group'][$filter_name];
                                $query->orWhere($this->getBaseColumnName($filter[0], $filterRelation), $filter[1],
                                    $filter[2]);
                            }
                        });
                    });
            } elseif ($filterScope) {
                $scope_filters = [];
                foreach ($filter_names as $filter_name) {
                    $scope_filters[] = $grouped_filters['group'][$filter_name];
                }
                $query = $query->$filterScope($scope_filters);
            } else {
                $query = $query->where(function ($q) use ($filter_names, $grouped_filters) {
                    foreach ($filter_names as $filter_name) {
                        $filter = $grouped_filters['group'][$filter_name];
                        $q->orWhere($this->getBaseColumnName($filter[0]), $filter[1], $filter[2]);
                    }
                });
            }
        }

    }

    /*
     * adds where of whereIn etc to query
     */
    private function addWhere(&$query, $column, $operator, $value)
    {
        if ($operator == 'in') {

            $is_null = false;
            foreach ($value as $key => $val) {
                if ($val === null) {
                    $is_null = true;
                    unset($value[$key]);
                }
            }

            if ($is_null) {
                $query->whereNull($column)->orWhereIn($column, $value);
            } else {
                $query->whereIn($column, $value);
            }

        } elseif ($operator == 'not in') {
            $is_null = false;
            foreach ($value as $key => $val) {
                if ($val === null) {
                    $is_null = true;
                    unset($value[$key]);
                }
            }

            $value = array_values($value);
            if ($is_null) {
                $query->whereNotNull($column)->whereNotIn($column, $value);
            } else {
                $query->whereNotIn($column, $value);
            }

        } elseif ($operator == 'search') {

            $input = mb_strtolower($value);
            $input = Transliterator::transliterate($input, ' ');
            $input = explode(' ', $input);
            $input = array_filter($input);

            foreach ($input as $word) {
                $query = $query->where('search', 'LIKE', '%' . $word . '%');
            }

        } else {
            $query->where($column, $operator, $value);
        }
        return $query;
    }

    /*
     *add default filters to ours
     */
    private function addDefaultFiltersToParams($params, $def_filters)
    {
        foreach ($def_filters as $def_filter) {
            if (!isset($def_filter['by_scope'])) {
                $new_param = [
                    'type' => 'filters',
                    'key' => $def_filter['table_column'] . $def_filter['value'],
                    'value' => [$def_filter['table_column'], $def_filter['operator'], $def_filter['value'], $def_filter['table_column'] . $def_filter['value']],
                ];
                $params = $this->urlData->addParam($params, $new_param);
            }
        }

        return $params;
    }

    /*
     *group filters with single and multiple values
     */
    private function groupFilters($params)
    {
        $or_check = [];
        $grouped_filters = [
            'single' => [],
            'group' => [
                'group_marks' => []
            ]
        ];

        foreach ($params['filters'] as $iteration => $filter) {
            //group our filters
            if (isset($or_check[$filter[3]])) {
                //if we have repeat of single filter we should remove it to group filters
                $prev_iteration = $or_check[$filter[3]];
                if (isset($grouped_filters['single'][$prev_iteration])) {
                    $grouped_filters['group'][$prev_iteration] = $grouped_filters['single'][$prev_iteration];
                    $grouped_filters['group']['group_marks'][$filter[3]][] = $prev_iteration;
                    unset($grouped_filters['single'][$prev_iteration]);
                }

                $grouped_filters['group'][$iteration] = $filter;
                $grouped_filters['group']['group_marks'][$filter[3]][] = $iteration;

            } else {
                $or_check[$filter[3]] = $iteration;
                $grouped_filters['single'][$iteration] = $filter;
            }
        }

        return $grouped_filters;
    }

    /*
     *get relation of the filter
     */
    private function checkFilterRelation($filter_name)
    {
        $relation = false;

        if (isset($this->urlData->settings['filters'][$filter_name]['to_relation'])) {
            $relation = $this->urlData->settings['filters'][$filter_name]['to_relation'];
        }

        return $relation;
    }

    /*
     *get scope of the filter
     */
    private function checkFilterScope($filter)
    {
        $scope = false;

        if (isset($this->urlData->settings['filters'][$filter]['by_scope'])) {
            $scope = $this->urlData->settings['filters'][$filter]['by_scope'];
        }

        return $scope;
    }

    /*
     *add sorts to our query
     */
    private function addSorts($query)
    {

        $def_constant = isset($this->urlData->settings['default']['sorts_const']) ? $this->urlData->settings['default']['sorts_const'] : [];
        $def_changeable = isset($this->urlData->settings['default']['sorts']) ? $this->urlData->settings['default']['sorts'] : [];
        $active = $this->urlData->params['sorts'];

        //sorts that can't be replased by user
        foreach ($def_constant as $def_value) {
            $this->addSortCase($query, $def_value['source'], $def_value['value'], $def_value['order']);
        }

        //set def value for sorts that can be changed by user
        if (!empty($def_changeable)) {
            $sort_by = $def_changeable['value'];
            $sort_order = $def_changeable['order'];
            $sort_case = $def_changeable['source'];
        }

        //replace def values by values from url
        if (isset($active['sort_order'])) {
            $sort_order = $active['sort_order'];
        }
        if (isset($active['sort_item'])) {
            $sort_by = $active['sort_item']['sort_by'];
            $sort_case = $active['sort_item']['type'];
        }

        if (isset($sort_by)) {
            $this->addSortCase($query, $sort_case, $sort_by, $sort_order);
        }
        $query->orderBy($this->getBaseColumnName('id'), 'asc');
    }

    /*
     *adds sorts depending of data source
     */
    private function addSortCase($query, $sort_case, $sort_by, $sort_order)
    {
        switch ($sort_case) {
            case 'by_column':
                $query->orderBy($sort_by, $sort_order);
                break;
            case 'by_scope':
                $query->$sort_by($sort_order);
                break;
        }
    }

    /*
     *returns full column name for cases with aggregation in request
     */
    private function getBaseColumnName($column_name, $relation = false)
    {

        if (isset($this->urlData->settings['has_aggregation']) && $this->urlData->settings['has_aggregation'] == true) {
            if ($relation) {
                $column_name = with(new $this->urlData->params['model'])->$relation()->getRelated()->getTable() . '.' . $column_name;
            } else {
                $column_name = with(new $this->urlData->params['model'])->getTable() . '.' . $column_name;
            }
        }

        return $column_name;
    }

}