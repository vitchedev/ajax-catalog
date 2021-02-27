<?php

namespace Vaden\AjaxCatalog;

use Vaden\AjaxCatalog\Widgets\Breadcrumbs;
use Vaden\AjaxCatalog\Widgets\WidgetsFactory;
use Vaden\AjaxCatalog\Widgets\CurrentSearch;
use Vaden\AjaxCatalog\Widgets\CurrentTextFieldValues;
use Vaden\AjaxCatalog\Widgets\Pagination;
use Vaden\AjaxCatalog\Widgets\Sorts;
use Illuminate\Http\Request;
use Excel;
use DB;
use Gate;
use App\Http\Controllers\Controller as Controller;
use Route;
use Vaden\AjaxCatalog\AjaxCatalogPage;
use Cache;

class AjaxCatalogController extends Controller
{

    /*
     *returns routes for catalog
     */
    static function routes()
    {

        //we create route for each entity type from package config file
        $entities = config('ajaxCatalog.entities_list');
        $short_urls_models = config('ajaxCatalog.short_urls');

        if ($short_urls_models) {
            $short_urls = [];
            //get urls from all models
            foreach ($short_urls_models as $model) {
                $short_urls = $short_urls + $model::ajaxCatalogShortUrls();
            }

            //process short urls
            foreach ($short_urls as $alias => $short_url) {
                AjaxCatalogController::generateFiltersRoutes($alias, $alias);
            }
        }

        foreach ($entities as $value) {
            if (isset($value['rout_group'])) {
                Route::prefix($value['rout_group'])->group(function () use ($value) {
                    AjaxCatalogController::generateFiltersRoutes($value['entity_alias'], $value['route_alias']);
                });
            } else {
                AjaxCatalogController::generateFiltersRoutes($value['entity_alias'], $value['route_alias']);
            }
        }

        //export of results of search
        Route::post('/f-export', '\Vaden\AjaxCatalog\AjaxCatalogController@export')->name('catalogExport');

        //handler of bulk operations
        Route::post('/f-bulk-operations',
            '\Vaden\AjaxCatalog\AjaxCatalogController@bulkOperations')->name('catalogBulkOperations');

        //for ajax form fields
        Route::post('/f-ajax-field', '\Vaden\AjaxCatalog\AjaxCatalogController@ajaxField')->name('catalogAjaxField');
        Route::post('/f-ajax-autocomplete',
            '\Vaden\AjaxCatalog\AjaxCatalogController@ajaxAutocomplete')->name('catalogAjaxAutocomplete');
        Route::post('/f-ajax-autocomplete-2',
            '\Vaden\AjaxCatalog\AjaxCatalogController@ajaxAutocomplete2')->name('catalogAjaxAutocomplete2');

        Route::post('/f-page-text-ajax-update', '\Vaden\AjaxCatalog\AjaxCatalogPageController@textAjaxUpdate');

        //for ajax catalog pages form
        Route::get('/admin/ajax-catalog-page/create',
            '\Vaden\AjaxCatalog\AjaxCatalogPageController@create')->name('ajaxCatalogPage.create');
        Route::post('/admin/ajax-catalog-page/store',
            '\Vaden\AjaxCatalog\AjaxCatalogPageController@store')->name('ajaxCatalogPage.store');

        Route::get('/admin/ajax-catalog-page/{item}/edit',
            '\Vaden\AjaxCatalog\AjaxCatalogPageController@edit')->name('ajaxCatalogPage.edit');
        Route::post('/admin/ajax-catalog-page/{item}/update',
            '\Vaden\AjaxCatalog\AjaxCatalogPageController@update')->name('ajaxCatalogPage.update');
        Route::get('/admin/ajax-catalog-page/{item}/delete',
            '\Vaden\AjaxCatalog\AjaxCatalogPageController@delete')->name('ajaxCatalogPage.delete');

    }

    /*
     *creates routes for filters
     */
    public static function generateFiltersRoutes($entity_alias, $route_alias)
    {
        Route::get('/' . $entity_alias,
            '\Vaden\AjaxCatalog\AjaxCatalogController@show')->name($route_alias);
        Route::get('/' . $entity_alias . '/{all}',
            '\Vaden\AjaxCatalog\AjaxCatalogController@show')->where(['all' => '.*'])->name($route_alias . '.catalogFilters');
    }

    /*
     *for pages with filters
     */
    public function show(Request $request, $all = '')
    {
        $urlData = new UrlConverter($request->getRequestUri());

        if (\Auth::user() == null) {
            if ($cache = Cache::tags([
                config('app.name'),
                config('app.name') . 'ajax_catalog_anonim_page'
            ])->get($urlData->originUrl)
            ) {
                return $this->renderPage($urlData, $cache);
            }
        }

        //check access for the page
        $this->checkPermission($urlData);

        //get data about items for our catalog page
        $query = new ResultsGenerator($urlData);
        $queryset = $query->getData();
        $items_count = $query->items_count;
        $filters_add_info = $query->filters_add_info;

        //add pagination
        $pageData = [];
        $pageData['filter_data'] = $this->getFiltersDataForPage($urlData);
        $pageData['master_blade'] = isset($urlData->settings['master_blade']) ? $urlData->settings['master_blade'] : 'layouts.master';
        $pageData['cache'] = isset($urlData->settings['cache']) ? $urlData->settings['cache'] : false;
        $paginator = new Pagination($urlData, $queryset, $items_count);
        $pageData['itemsCount'] = $paginator->items_count;
        $pageData['itemsData'] = $paginator->pageItems();
        $pageData['paginationInfo'] = $paginator->info;

        //generate our widgets from settings
        $this->getWidgetsData($paginator, $urlData, $pageData);

        //fields for export form
        //$pageData['export_fields'] = isset($urlData->settings['export_fields']) ? $urlData->settings['export_fields'] : [];

        //settings for bulk operations
        $pageData['bulk_operations'] = isset($urlData->settings['bulk_operations']) ? $urlData->settings['bulk_operations'] : [];

        //additional info for pages
        if (isset($urlData->settings['additional_info'])) {
            $link = str_replace('/jq', '', $urlData->originUrl);
            $ajax_catalog_page = AjaxCatalogPage::where('link', $link)->with('seo')->first();
            $add_info = new AddInfo($urlData->settings['additional_info'], $pageData['itemsData'],
                $urlData->params['filters'], $filters_add_info, $ajax_catalog_page);
            $pageData['add_info'] = $add_info->getData($pageData['itemsData']);
            foreach ($pageData['add_info'] as $add_key => $add_val) {
                if (isset($urlData->settings['additional_info'][$add_key]['to_js']) && $urlData->settings['additional_info'][$add_key]['to_js'] == true) {
                    $pageData['data_for_js'][$add_key] = $add_val;
                }
            }
        } else {
            $pageData['add_info'] = [];
        }

        foreach ($urlData->for_add_info as $url_key => $url_add) {
            $pageData['add_info'][$url_key] = $url_add;
        }

        $pageData['jq'] = $urlData->params['jq']['state'];

        //pass info about our rout groupe to js for ajax text fields
        $pageData['seo'] = $this->getSeo($urlData);

        if (\Auth::user() == null) {
            if ($pageData['cache']) {
                Cache::tags([
                    config('app.name'),
                    config('app.name') . 'ajax_catalog_anonim_page'
                ])->put($urlData->originUrl, $pageData, 40);
            }
        }

        return $this->renderPage($urlData, $pageData);
    }

    /*
     * get seo fields for ajax catalog
     */
    private function getSeo($urlData)
    {

        $seo = [];

        $seo['title'] = 'Title';
        $seo['h1'] = 'H1';

        $link = str_replace('/jq', '', $urlData->originUrl);

        $meta = AjaxCatalogPage::where('link', $link)->with('seo')->first();
        if ($meta) {
            $seo['title'] = $meta->name;
            $seo['text'] = $meta->text;
            if (isset($meta->seo[0])) {
                $metaSeo = $meta->seo[0];
                $seo['title'] = $metaSeo->title;
                $seo['h1'] = $meta->name;
                $seo['description'] = $metaSeo->description;
            }
            return $seo;
        }

        if (isset($urlData->settings['title'])) {
            if ($urlData->settings['title'] == 'default') {
                $seo['title'] = $urlData->settings['name'];
            } else {
                foreach ($urlData->params['filters'] as $key => $filter) {
                    if ($filter[3] == $urlData->settings['title']) {
                        $seo['title'] = $urlData->settings['filters'][$filter[3]]['name'] . ' ' . $urlData->values[$filter[3]][$key]['name'];
                    }
                }
            }
            $seo['h1'] = $seo['title'];
            return $seo;
        }

        return $seo;
    }

    private function getFiltersDataForPage($urlData)
    {
        $data = [];
        if ($urlData->params['filters']) {
            $filter_param = $urlData->params['filters'];
            foreach ($urlData->params['filters'] as $filter) {
                $data['current_filters'][$filter[3]] = [
                    'settings' => $urlData->settings['filters'][$filter[3]],
                    'value' => $filter[2]
                ];
            }
            $urlData->settings['filter_data']['param'] = array_shift($filter_param)[2];
        }
        if (isset($urlData->settings['filter_data'])) {
            $data['current_page'] = $urlData->settings['filter_data'];
        }

        return $data;
    }

    /*
     * generate widgets data for page
     */
    private function getWidgetsData($paginator, $urlData, &$pageData)
    {

        $pageData['data_for_js'] = [];

        if ($cache = Cache::tags([
            config('app.name'),
            config('app.name') . 'ajax_catalog_widgets_data'
        ])->get($urlData->originUrl)
        ) {
            $pageData['widgetsData'] = $cache['widgets'];
            $pageData['data_for_js'] = $cache['js'];
        } else {
            $widgetsData = [];
            $widgetsData['pagination'] = $paginator->getData();

            //add sorts
            $widget = new Sorts($urlData);
            $widgetsData['sorts'] = $widget->getData();

            //add current search
            $widget = new CurrentSearch($urlData);
            $widgetsData['currSearch'] = $widget->getData();

            //add breadcrumbs
            $widget = new Breadcrumbs($urlData);
            $widgetsData['breadcrumbs'] = $widget->getData();

            //add values for current text fields
            $widget = new CurrentTextFieldValues($urlData);
            $widgetsData['currTextValues'] = $widget->getData();
            //add filter widgets
            $filters = isset($urlData->settings['filters']) ? $urlData->settings['filters'] : [];
            foreach ($filters as $widget_key => $widget_value) {
                if (isset($widget_value['widget'])) {
                    $widget = WidgetsFactory::create($urlData, $widget_key, $widget_value['name'],
                        $widget_value['widget']);
                    $widgetsData[$widget_key] = $widget->getData();
                    if (isset($widgetsData[$widget_key]['to_js'])) {
                        unset($widgetsData[$widget_key]['to_js']);
                        $pageData['data_for_js'][$widget_key . 'Values'] = $widgetsData[$widget_key];
                    }
                }
            }

            if ($pageData['cache']) {
                Cache::tags([
                    config('app.name'),
                    config('app.name') . 'ajax_catalog_widgets_data'
                ])->put($urlData->originUrl, ['widgets' => $widgetsData, 'js' => $pageData['data_for_js']], 40);
            }
            $pageData['widgetsData'] = $widgetsData;
        }
    }


    /*
    *check that user can see necessary page
    */
    private function renderPage($urlData, $pageData)
    {
        if ($urlData->params['jq']['state']) {
            $html = view($urlData->settings['blade'], $pageData)->render();
            $zones = [
                'reload_page' => true,
                'zones' => [],
                'data_for_js' => $pageData['data_for_js']
            ];

            //if (isset($urlData->settings['load_zones'])) {
            if (false) {
                $doc = new \DOMDocument();
                $doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                foreach ($urlData->settings['load_zones'] as $zone => $update) {
                    if ($update) {
                        $tags = $doc->getElementById($zone);
                        if ($tags != null) {
                            $tags = $doc->saveHTML($tags);
                            $zones[$zone] = $tags;
                        }
                    } else {
                        $zones[$zone] = false;
                    }
                }
            } else {
                $zones['zones']['catalog_page_ajax'] = $html;
            }
            return \Response::json($zones);
        } else {
            \JavaScript::put($pageData['data_for_js']);
            return view($urlData->settings['blade'], $pageData);
        }
    }

    /*
    *check that user can see necessary page
    */
    private
    function checkPermission(
        $urlData
    )
    {
        //check if url is valid
        if (!$urlData->params) {
            return abort(404);
        }

        //check rights
        if (isset($urlData->settings['gate']) && $urlData->settings['gate'] !== false) {
            if (isset($urlData->settings['gate']['add_info']) && $urlData->settings['gate']['add_info'] != false) {

                $new_data = null;
                $search_for = null;

                foreach ($urlData->settings['gate']['add_info'] as $add_entity) {

                    foreach ($urlData->params['filters'] as $check_filter) {
                        if ($check_filter[3] == $add_entity['filter_name']) {
                            $search_for = $check_filter[2];
                        }
                    }

                    $new_data = $add_entity['model']::find($search_for);
                }

                if (!Gate::allows($urlData->settings['gate']['name'], $new_data)) {
                    return abort('403');
                }

            } else {
                if (!Gate::allows($urlData->settings['gate']['name'])) {
                    return abort('403');
                }
            }
        }
    }

    /*
    *redirect user back after bulk operation execute
    */
    public function bulkOperations(Request $request)
    {
        //get data about our url
        $urlData = new UrlConverter($request['requestUrl']);
        $params = $urlData->params;

        //get items to work with
        $itemsArr = $request['items'] == null ? [] : $request['items'];

        //porcess our operation by possible types
        $settings = $urlData->settings['bulk_operations'][$request['operation']];
        switch ($settings['type']) {
            case 'model':
                foreach ($itemsArr as $item_id) {
                    $item = $settings['model']::find($item_id);
                    $method = $settings['method'];
                    $item->$method();
                }
                break;
            case 'db_query':
                //update data in our database
                $updated = DB::table($settings['table'])
                    ->whereIn('id', $itemsArr)
                    ->update([$settings['column'] => $settings['set_to']]);
                break;
            case 'export':
                //get data for export
                $query = new ResultsGenerator($urlData);
                $itemsData = $query->getData($itemsArr)->get();

                //export checked items
                $alter = $settings['alter'] ?? false;

                if ($alter) {
                    $class = $alter['class'];
                    $method = $alter['method'];

                    $class::$method($itemsData);
                } else {
                    $fieldsArr = $urlData->settings['export_fields'];
                    $this->createExel($fieldsArr, $itemsData, $urlData->params['entity']);
                }

                break;
        }

        return 'success';
    }

    /*
    *export our data
    */
    public function export(Request $request)
    {
        $urlData = new UrlConverter($request['requestUrl']);
        //array of fields for export
        $fieldsArr = $request->all();
        unset($fieldsArr['_token']);
        unset($fieldsArr['requestUrl']);
        unset($fieldsArr['check_all']);

        //get data for export
        $query = new ResultsGenerator($urlData);
        $queryset = $query->getData($fieldsArr);

        //add pagination
        $paginator = new Pagination($urlData, $queryset);
        $itemsData = $paginator->pageItems();

        // Define the Excel spreadsheet headers
        foreach ($fieldsArr as $arr_key => $arr_value) {
            $fieldsArr[$arr_key] = $urlData->settings['export_fields'][$arr_key]['name'];
        }

        $this->createExel($fieldsArr, $itemsData, $urlData->params['entity']);
    }

    /*
    *store imported host data
    */
    protected function createExel(
        $fields,
        $items,
        $entity
    )
    {
        // Initialize the array which will be passed into the Excel generator.
        $exportArray = [];

        //add titles for our columns
        $fieldTitles = [];
        foreach ($fields as $fieldTitle) {
            $fieldTitles[] = $fieldTitle['name'];
        }
        $exportArray[] = $fieldTitles;

        // Convert each member of the returned collection into an array
        foreach ($items as $item) {
            $exportArray[] = $this->getArrayValueFromModelItem($fields, $item);
        }

        // Generate and return the spreadsheet
        ob_end_clean();
        $excel = Excel::create($entity . ' export ' . time(), function ($excel) use ($exportArray) {

            // Build the spreadsheet, passing in the payments array
            $excel->sheet('sheet1', function ($sheet) use ($exportArray) {
                $sheet->fromArray($exportArray, null, 'A1', false, false);
            });

        })->download('xlsx');
    }

    protected function getArrayValueFromModelItem($fields, $item)
    {
        $arr = [];

        foreach ($fields as $field) {
            $attr = $field['column'];

            if (isset($field['to_relation'])) {
                $relation = $field['to_relation'];
                $relation = $item->$relation()->first();
                $arr[] = $relation == null ? null : $relation->$attr;
            } else {
                $arr[] = $item->$attr;
            }
        }

        return $arr;
    }

    /*
    *handler for ajax text fields
    */
    public function ajaxField(Request $request)
    {

        $this->validate($request, [
            'name' => 'required|string|max:248',
            'url' => 'required|string|max:2048',
            'text' => 'nullable|string|max:512',
        ]);

        $text = str_replace(array("\\", '/', '%'), '_', strip_tags($request['text']));

        //alias for our filter
        $alias = $request['name'] . '-' . $text;

        //get data about our url
        $urlData = new UrlConverter(substr($request['url'], 1));
        $params = $urlData->params;
        if ($params == false) {
            abort('404');
        }

        $filters = isset($params['filters']) ? $params['filters'] : [];

        //remove new values from url before add new
        foreach ($filters as $param_key => $param_value) {
            $param_key_arr = explode('-', $param_key);
            if ($param_key_arr[0] == $request['name']) {
                unset($params['filters'][$param_key]);
            }
        }

        if ($text !== null) {
            //add to filter value of current link
            $newParam = $urlData->paramFromAlias($alias);
            $params = $urlData->addParam($params, $newParam);
        }

        //get url for our form result
        $url = $urlData->urlGenerate($params);


        return $url;
    }

    /*
    *handler for ajax text fields with autocomplete
    */
    public
    function ajaxAutocomplete(
        Request $request
    )
    {
        $input = trim($request['q']);

        if (empty($input)) {
            return \Response::json([]);
        }
        $column = $request['column'];

        $input_arr = explode(' ', $input);
        $input = '%' . implode('%', $input_arr) . '%';

        $result = DB::table($request['table'])->where($column, 'LIKE', $input)->paginate(10);

        $formatted = [];

        foreach ($result as $value) {
            $formatted[] = ['id' => $value->$column, 'text' => $value->$column];
        }

        return \Response::json($formatted);
    }

    /*
    *handler for ajax text fields with autocomplete 2
    */
    public
    function ajaxAutocomplete2(
        Request $request
    )
    {
        $input = trim($request['q']);

        if (empty($input)) {
            return \Response::json([]);
        }
        $column = $request['column'];

        $input_arr = explode(' ', $input);
        $input = '%' . implode('%', $input_arr) . '%';

        $result = DB::table($request['table'])->where($column, 'LIKE', $input)->paginate(10);

        $formatted = [];

        foreach ($result as $value) {
            $formatted[] = $value->$column;
        }

        return \Response::json($formatted);
    }

}
