<?php

return [

    'entities_list' => [
        [
            'model' => 'Vaden\AjaxCatalog\AjaxCatalogPage',
            'route_alias' => 'admin.ajaxCatalogPage',
            'settings_key' => 'ajax_catalog_pages',
            'rout_group' => 'admin',
            'entity_alias' => 'ajax-catalog-pages',
        ],
    ],

    'breadcrumbs' => [
        'name' => 'Главная',
        'title' => 'Перейти на главную',
        'deliminer' => '»',
    ],

    'ajax_catalog_pages' => [
        'alias' => 'admin/ajax-catalog-pages',
        'blade' => 'ajaxCatalog.ajaxCatalogPage',
		'master_blade' => 'layouts.adminMaster',
        'pagination' => 50,
        'filters' => [
            'name' => [
                'name' => 'Название',
                'table_column' => 'name',
                'operator' => 'like',
                'filter_type' => 'search',
            ],
            'cab' => [
                'name' => 'Создана от',
                'table_column' => 'created_at',
                'operator' => '>=',
                'filter_type' => 'date',
            ],
            'cae' => [
                'name' => 'Создана до',
                'table_column' => 'created_at',
                'operator' => '<=',
                'filter_type' => 'date',
            ],
            'uab' => [
                'name' => 'Изменена от',
                'table_column' => 'updated_at',
                'operator' => '>=',
                'filter_type' => 'date',
            ],
            'uae' => [
                'name' => 'Изменена до',
                'table_column' => 'updated_at',
                'operator' => '<=',
                'filter_type' => 'date',
            ],
        ],
        'sorts' => [
            [
                'name' => 'Дата создания',
                'alias' => 'ct',
                'by_column' => 'created_at'
            ],
            [
                'name' => 'Дата обновления',
                'alias' => 'ut',
                'by_column' => 'updated_at'
            ],
            [
                'name' => 'Название',
                'alias' => 'nm',
                'by_column' => 'name',
            ],
        ],
        'default' => [
            'sorts' => [
                'order' => 'desc',
                'source' => 'by_column',
                'value' => 'created_at'
            ],
        ],
        'bulk_operations' => [
            'edit' => [
                'name' => 'Изменить',
                'check' => false,
                'type' => 'link',
                'gate' => false,
            ],
            'delete' => [
                'name' => 'Удалить',
                'check' => true,
                'type' => 'model',
                'model' => 'Vaden\AjaxCatalog\AjaxCatalogPage',
                'method' => 'delete',
                'gate' => false,
            ],
        ],
    ]
];
