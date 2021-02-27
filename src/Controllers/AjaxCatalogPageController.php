<?php

namespace Vaden\AjaxCatalog;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use JavaScript;
use Asmet\SeoModule\Models\Seo;
use Gate;

class AjaxCatalogPageController extends Controller
{
    // форма создания категории
    public function create()
    {
        if (!Gate::allows('admin_ajax_catalog')) {
            return abort('403');
        }

        JavaScript::put([
            'item' => [],
            'thumbs' => [],
            'successMessage' => __('ajaxCatalog.catalog_page_created'),
            'successButton' => __('ajaxCatalog.catalog_page_created_btn')
        ]);

        return view('ajaxCatalog.pages.createOrEdit');
    }

    // сохраняем категорию
    public function store(Request $request)
    {
        if (!Gate::allows('admin_ajax_catalog')) {
            return abort('403');
        }

        if ($request['content'] === '<p><br></p>') {
            $request['content'] = null;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'link' => 'required|url|max:255',
            'title' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:255',
            'menu' => 'sometimes|nullable|string:max:255',
            'raw_url' => 'sometimes|nullable|string|max:255',
        ]);

        //check that url is valid
        $url = $this->checkedUrl($request['link']);
        if ($url == false) {
            return response()->json([
                'errors' => ['link' => [0 => __('ajaxCatalog.wrong_url')]]
            ], 400);
        }

        $item = new AjaxCatalogPage;
        $item->name = $request['name'];
        $item->link = $url;
        $item->text = editor_save($request['content']);
        $item->save();

        // создаем сео
        $seo = new Seo;
        $seo->title = $request['title'] ?? str_limit($item->name, 55);
        $seo->description = $request['description'] ?? str_limit($item->name, 155);
        $seo->seoable_id = $item->id;
        $seo->seoable_type = AjaxCatalogPage::class;
        $seo->menu = $request['menu'] ?? $item->name;
        $seo->save();

        $additional_model = config('ajaxCatalog.additional_model');
        if (isset($additional_model['controller'])) {
            app($additional_model['controller'])->updateOrCreate($request, $item);
        }

        if ($request->ajax()) {
            return response()->json([
                'reset' => true,
                'url' => url($item->link)
            ]);
        }

        return ['message' => 'Success!'];
    }

    public function edit(AjaxCatalogPage $item)
    {
        if (!Gate::allows('admin_ajax_catalog')) {
            return abort('403');
        }

        $item->seo->toArray();
        $thumbs = [];

        $model = config('ajaxCatalog.additional_model')['model'];
        if (class_exists($model)) {
            $item['ajaxCatalogAdditionalFields'] = $item->ajaxCatalogAdditionalFields;
            $item['ajaxCatalogAdditionalCategoryFields'] = $item->ajaxCatalogAdditionalFields->ajaxCatalogAdditionalCategoryFields ?? [];
            $thumbs = isset($item->ajaxCatalogAdditionalFields) ? $item->ajaxCatalogAdditionalFields->getFilesThumbs() : [];
        }

        JavaScript::put([
            'item' => $item->toArray(),
            'thumbs' => $thumbs,
            'successMessage' => __('ajaxCatalog.catalog_page_edited'),
            'successButton' => __('ajaxCatalog.catalog_page_created_btn')
        ]);

        return view('ajaxCatalog.pages.createOrEdit', compact('item'));
    }

    // сохраняем категорию
    public function update(Request $request, AjaxCatalogPage $item)
    {
        if (!Gate::allows('admin_ajax_catalog')) {
            return abort('403');
        }

        if ($request['content'] === '<p><br></p>') {
            $request['content'] = null;
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string|max:255',
            'menu' => 'sometimes|nullable|string:max:255',
            'link' => 'required|nullable|string|max:255',
            'raw_url' => 'sometimes|nullable|string|max:255',
        ]);

        //check that url is valid
        $url = $this->checkedUrl($request['link'], $item->link);
        if ($url == false) {
            return response()->json([
                'errors' => ['link' => [0 => __('ajaxCatalog.wrong_url')]]
            ], 400);
        }

        $item->name = $request['name'];
        $item->link = $url;
        $item->text = editor_save($request['content'], $item->text);
        $item->save();

        // перезаписываем сео
        $seo = $item->seo->first();
        $seo->title = $request['title'] ?? str_limit($item->name, 55);
        $seo->description = $request['description'] ?? str_limit($item->name, 155);
        $seo->seoable_id = $item->id;
        $seo->seoable_type = AjaxCatalogPage::class;
        $seo->menu = $request['menu'] ?? $item->name;
        $seo->save();

        $additional_model = config('ajaxCatalog.additional_model');
        if (isset($additional_model['controller'])) {
            app($additional_model['controller'])->updateOrCreate($request, $item);
        }

        return [
            'noReset' => true,
            'url' => url($item->link)
        ];
    }

    // delete item
    public function delete(AjaxCatalogPage $item)
    {
        if (!Gate::allows('admin_ajax_catalog')) {
            return abort('403');
        }

        editor_delete($item->text);

        $item->delete();

        return redirect(route('admin.ajaxCatalogPage'));
    }

    private function checkedUrl($url, $old_url = null)
    {
        if (mb_substr($url, -1) == '/') {
            $url = mb_substr($url, 0, -1);
        }

        if ($old_url == null || $old_url != $url) {

            $url = str_replace(\App::make('url')->to('/'), "", $url);

            $url_check = AjaxCatalogPage::where('link', $url)->first();
            if ($url_check != null && $old_url != $url) {
                $url = false;
            }
        }

        return $url;
    }
}