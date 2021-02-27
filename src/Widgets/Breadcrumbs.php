<?php

namespace Vaden\AjaxCatalog\Widgets;

class Breadcrumbs extends Widgets
{
    public $urlData;
    private $deliminer = '»';

    public function __construct($urlData)
    {
        $this->urlData = $urlData;
    }

    /*
     *returns array with links of the widget
    */
    public function getData()
    {
        $links = [];

        $links = $this->addDefaultLinks($links);
        $links = $this->addDynamicLinks($links);

        $count = count($links) - 1;
        if($count != 0) {
            $links[$count]['widget_link'] = false;
        }

        foreach ($links as $key => $link) {
            $link['deliminer'] = $this->deliminer;
            $links[$key] = $link;
        }

        return $links;
    }

    /*
     * add links from settings, that present on all catalog pages
     */
    private function addDefaultLinks($links)
    {

        if ($settings = config('ajaxCatalog.breadcrumbs')) {
            $crumb = [
                'url' => '/',
                'ankor' => $settings['name'],
                'title' => $settings['title'],
                'widget_link' => true
            ];
            $this->deliminer = $settings['deliminer'];
            $links[] = $crumb;
        } else {
            $crumb = [
                'url' => '/',
                'ankor' => 'Главная',
                'title' => 'Главная',
                'widget_link' => true
            ];
            $links[] = $crumb;
        }

        if (isset($this->urlData->settings['breadcrumbs'])) {
            foreach ($this->urlData->settings['breadcrumbs'] as $crumb) {
                $crumb['widget_link'] = true;
                $links[] = $crumb;
            }
        }

        return $links;
    }

    /*
     * add links from active filters
     */
    private function addDynamicLinks($links)
    {

        $filter_crumbs = [];

        foreach ($this->urlData->params['filters'] as $key => $filter) {
            $settings = isset($this->urlData->settings['filters'][$filter['3']]['breadcrumbs']) ? $this->urlData->settings['filters'][$filter['3']]['breadcrumbs'] : false;
            if ($settings) {
                $filter_crumbs[$filter[3]]['settings'] = $settings;
                $filter_crumbs[$filter[3]][$key] = $filter;
            }
        }

        foreach ($filter_crumbs as $filterName => $fiterGroup) {
            $links = array_merge($links, $this->getCrumbsFromFilterGroups($fiterGroup));
        }



        return $links;
    }

    /*
     * returns url for breadcrumb
     */
    private function generateCrumbUrl($fiterAlias)
    {

        $fullUrl = explode('/', $this->urlData->originUrl);
        $check = false;

        foreach ($fullUrl as $key => $value) {
            if ($check) {
                unset($fullUrl[$key]);
            }
            if ($fiterAlias == $value) {
                $check = true;
            }
        }

        return '/' . implode('/', $fullUrl);
    }

    /*
     * returns url for breadcrumb
     */
    private function getCrumbsFromFilterGroups($fiterGroup)
    {

        $crumbs = [];

        if ($fiterGroup['settings']['separate']) {
            foreach ($fiterGroup as $key => $filter) {
                if ($key != 'settings') {
                    $values_key = $this->urlData->settings['filters'][$filter['3']]['filter_type'] == 'name' ? $key : $filter[2];
                    $name = $this->urlData->values[$filter[3]][$values_key]['name'];
                    $filterName = $this->urlData->settings['filters'][$filter['3']]['name'];

                    if (!$fiterGroup['settings']['short_name']) {
                        $name = $filterName . ': ' . $name;
                    }

                    $crumb = [
                        'url' => $this->generateCrumbUrl($key),
                        'ankor' => $name,
                        'title' => $name,
                        'widget_link' => true
                    ];
                    $crumbs[] = $crumb;
                }
            }
        } else {
            $name = [];
            $i = 0;
            $count = count($fiterGroup) - 1;
            foreach ($fiterGroup as $key => $filter) {
                if ($key != 'settings') {
                    $i++;
                    if ($i == 1) {
                        $filterName = $this->urlData->settings['filters'][$filter['3']]['name'];
                    }
                    if ($count == $i) {
                        $url = $this->generateCrumbUrl($key);
                    }

                    $values_key = $this->urlData->settings['filters'][$filter['3']]['filter_type'] == 'name' ? $key : $filter[2];
                    $name[] = $this->urlData->values[$filter[3]][$values_key]['name'];
                }
            }

            //dd($name);
            if ($fiterGroup['settings']['short_name']) {
                $name = implode(', ', $name);
            } else {
                $name = $filterName . ': ' . implode(', ', $name);
            }

            $crumb = [
                'url' => $url,
                'ankor' => $name,
                'title' => $name,
                'widget_link' => true
            ];

            if ($parentCrumbs = $this->addPrentCrumbs($fiterGroup, $url)) {
                $crumbs = array_merge($crumbs, $parentCrumbs);
            }

            $crumbs[] = $crumb;
        }

        return $crumbs;
    }

    /*
     * adds parent crumbs to crumb
     */
    private function addPrentCrumbs($fiterGroup, $childUrl)
    {
        $parents = false;

        if ($fiterGroup['settings']['with_depth']) {
            foreach ($fiterGroup as $key => $value) {
                if ($key != 'settings') {
                    $parentsArr = [];
                    $parentsArr = $this->findParentValues($parentsArr, $key, $value);
                    $parents = [];
                    foreach ($parentsArr as $parent) {
                        $name = $parent['name'];
                        $filterName = $this->urlData->settings['filters'][$value['3']]['name'];

                        if (!$fiterGroup['settings']['short_name']) {
                            $name = $filterName . ': ' . $name;
                        }

                        $crumb = [
                            'url' => $this->generateParentCrumbUrl($parent['value'], $value[3], $childUrl),
                            'ankor' => $name,
                            'title' => $name,
                            'widget_link' => true
                        ];
                        $parents[] = $crumb;
                    }

                    $parents = array_reverse($parents);
                }
            }
        }

        return $parents;
    }

    private function findParentValues($parents, $key, $value)
    {
        $values_key = $this->urlData->settings['filters'][$value['3']]['filter_type'] == 'name' ? $key : $value[2];
        $val = $this->urlData->values[$value[3]][$values_key];

        if (isset($val['parent']) && $val['parent'] != null) {
            $parent = $this->urlData->values[$value[3]][$val['parent']];
            $parents[$val['parent']] = $parent;
            $value[2] = $parent['value'];
            $parents = $this->findParentValues($parents, $val['parent'], $value);
        }

        return $parents;
    }

    private function generateParentCrumbUrl($value, $sysName, $childUrl)
    {

        $url = explode('/', $childUrl);
        $url = array_filter($url);
        $url = array_values($url);
        unset($url[count($url) - 1]);

        $filter_type = $this->urlData->settings['filters'][$sysName]['filter_type'];
        switch ($filter_type) {
            case 'prefix':
                $alias = $sysName . '-' . $value;
                break;
            case 'name':
                foreach ($this->urlData->values[$sysName] as $key => $val) {
                    if($val['value'] == $value) {
                        $alias = $key;
                        break;
                    }
                }
                break;
            default:
                $alias = $value;
        }

        $url[] = $alias;

        return '/' . implode('/', $url);
    }

}