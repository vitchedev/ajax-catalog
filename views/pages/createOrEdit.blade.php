@extends('layouts.adminMaster')

@section('title', __(isset($item) ? 'ajaxCatalog.editing_an_item' : 'ajaxCatalog.item_creation'))

@section('content')

    {!! !isset($item) ? get_breadcrumbs('createAjaxCatalogPage') : get_breadcrumbs('editAjaxCatalogPage', $item) !!}

    <div class="container-fluid">
        <div class="blog-header">
            <h1 class="text-center">@lang(isset($item) ? 'ajaxCatalog.editing_an_item' : 'ajaxCatalog.item_creation')</h1>
            <hr>
        </div>

        <div class="row">
            <div class="col-sm-12 blog-main">


                <form method="post"
                      class="form-horizontal"
                      action="{{ isset($item) ? route('ajaxCatalogPage.update', $item->id) : route('ajaxCatalogPage.store') }}"
                      id="ajaxCatalogPageForm"
                      @submit.prevent="onSubmit"
                      @keydown="form.errors.clear($event.target.name)">
                    {{ csrf_field() }}


                    <field-input label="@lang('ajaxCatalog.item_name')"
                                 v-model="form.name"
                                 :error="form.errors.get('name')"
                                 placeholder="@lang('ajaxCatalog.item_name')"
                                 name="name"></field-input>

                    <field-input label="@lang('ajaxCatalog.link')"
                                 v-model="form.link"
                                 :error="form.errors.get('link')"
                                 placeholder="@lang('ajaxCatalog.link')"
                                 name="link"></field-input>

                    <hr>
                    {{--seo fields start--}}
                    <div :class="{'sr-only': !seo}">

                        <field-input label="@lang('ajaxCatalog.page_title')"
                                     v-model="form.title"
                                     :error="form.errors.get('title')"
                                     placeholder="@lang('ajaxCatalog.page_title')"
                                     name="title"></field-input>

                        <field-input label="@lang('ajaxCatalog.on_menu')"
                                     v-model="form.menu"
                                     :error="form.errors.get('menu')"
                                     placeholder="@lang('ajaxCatalog.on_menu')"
                                     name="menu"></field-input>

                        <field-textarea label="@lang('ajaxCatalog.page_description')"
                                        v-model="form.description"
                                        :error="form.errors.get('description')"
                                        placeholder="@lang('ajaxCatalog.page_description')"
                                        name="description"></field-textarea>

                        <hr>
                    </div>
                    <a class="btn btn-success" @click="showSeo">@lang('ajaxCatalog.seo')</a>
                    {{--seo fields end--}}

                    <hr>

                    <div class="row">
                        <label class="col-xs-3">@lang('ajaxCatalog.text')</label>
                        <div class="col-xs-9 ckeditor_div">
                            <span v-if="form.errors.get('content')" v-text="form.errors.get('content')"></span>
                            <ckeditor model-name="content" v-model="form.content"></ckeditor>
                        </div>
                    </div>

                    <hr>

                    <div class="form-group">
                        <input type="submit" class="btn btn-primary" value="@lang('ajaxCatalog.save')"
                               :disabled="form.errors.any()" style="margin-left: 15px;">

                        <a href="{{ route('admin.ajaxCatalogPage') }}"
                           class="btn btn-info">@lang('ajaxCatalog.go_to_pages_manage')</a>

                        @if(isset($item))
                            <a href="{{ url($item->link) }}" class="btn btn-info">@lang('ajaxCatalog.go_to_show')</a>

                            <a href="{{ route('ajaxCatalogPage.delete', $item->id) }}" class="btn btn-default"
                               title="@lang('ajaxCatalog.delete')"
                               onclick="if( ! confirm('{{ __('ajaxCatalog.delete_confirmation') }}')){return false;}">
                                <span class="glyphicon glyphicon-remove icon-alert" aria-hidden="true"></span>
                                @lang('ajaxCatalog.delete')
                            </a>
                        @endif
                    </div>


                </form>

            </div>

        </div>
    </div>

@endsection