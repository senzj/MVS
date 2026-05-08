<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Class Namespace
    |--------------------------------------------------------------------------
    |
    | This value sets the root namespace for Livewire component classes in
    | your application. In addition to this namespace, the namespace may
    | contain sub-namespaces (directories) as well as suffixes.
    |
    | For more information, see the Livewire documentation:
    | https://livewire.laravel.com/docs/configuration
    |
    */

    'class_namespace' => 'App\\Livewire',

    /*
    |--------------------------------------------------------------------------
    | View Path
    |--------------------------------------------------------------------------
    |
    | This value sets the view path where Livewire component views are
    | stored. In addition to this path, the view path may contain
    | sub-directories (Livewire will look in sub-namespaces too).
    |
    */

    'view_path' => resource_path('views/livewire'),

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | The view that will be used as the layout when rendering the closest
    | sibling Livewire component within a stack.
    |
    */

    'layout' => 'components.layouts.app',

    /*
    |--------------------------------------------------------------------------
    | Lazy Placeholder
    |--------------------------------------------------------------------------
    |
    | The "lazy" directive (@lazy) is used to render a
    | component view in-place, but defer its Livewire
    | initialization until the component is visible.
    |
    */

    'lazy_placeholder' => null,

    /*
    |--------------------------------------------------------------------------
    | Temporary File Upload Path
    |--------------------------------------------------------------------------
    |
    | Directory path where temporary files will be stored during upload
    | before the developer moves them to their intended location.
    | If empty, Laravel's default temporary directory will be used.
    |
    */

    'temporary_file_upload_path' => '',

    /*
    |--------------------------------------------------------------------------
    | Temporary File Upload Cleanup
    |--------------------------------------------------------------------------
    |
    | Livewire automatically deletes temporary files older than
    | 24 hours. If you want to disable this feature, set this to false.
    |
    */

    'temporary_file_upload_cleanup' => true,

    /*
    |--------------------------------------------------------------------------
    | Render On Redirect
    |--------------------------------------------------------------------------
    |
    | This value determines if Livewire should re-render a component
    | after a redirect response has been returned by a Livewire action.
    |
    */

    'render_on_redirect' => false,

    /*
    |--------------------------------------------------------------------------
    | Preload Assets
    |--------------------------------------------------------------------------
    |
    | Disable or enable preloading of Livewire assets.
    | Setting to false will prevent the preload warning but may impact performance.
    |
    */

    'preload_assets' => false,

    /*
    |--------------------------------------------------------------------------
    | JSDoc Comments
    |--------------------------------------------------------------------------
    |
    | This value determines if Livewire will generate JavaScript Doc
    | comments inside generated JavaScript expressions.
    |
    */

    'js_doc_comments' => true,

];
