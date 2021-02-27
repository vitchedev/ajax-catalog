<?php

namespace Vaden\AjaxCatalog;


class AddInfo
{
    private $need_data;
    private $generator;
    private $filters;
    private $filters_add_info;
    private $ajax_catalog_page;

    public function __construct($need_data, $generator, $filters, $filters_add_info, $ajax_catalog_page)
    {
        $this->need_data = $need_data;
        $this->generator = $generator;
        $this->filters = $filters;
        $this->filters_add_info = $filters_add_info;
        $this->ajax_catalog_page = $ajax_catalog_page;
    }

    /*
     *get data that needs user
     */
    public function getData($items_data = [])
    {
        $data = [];
        if (!empty($this->need_data)) {
            foreach ($this->need_data as $data_key => $storage) {
                switch ($storage['type']) {
                    case 'config':
                        $data[$data_key] = config($storage['file']);
                        break;
                    case 'filter_item':
                        $id = 0;
                        foreach ($this->filters as $filter) {
                            if ($filter[3] == $storage['name']) {
                                $id = $filter[2];
                            }
                        }
                        $data[$data_key] = $storage['class']::find($id);
                        break;
                    case 'class_static':
                        $param = $storage['param'];
                        $method = $storage['method'];
                        if (isset($storage['add_data']) && $storage['add_data'] == 'catalogPage') {
                            $param = $this->ajax_catalog_page;
                        }
                        $data[$data_key] = $storage['name']::$method($param);
                        break;
                    case 'function':
                        $param = $storage['param'];
                        $data[$data_key] = $storage['name']($param);
                        break;
                    case 'result_generator':
                        $data[$data_key] = $this->filters_add_info[$storage['name']];
                        break;
                }
            }
        }
        return $data;
    }
}