<?php

use Knuckles\Scribe\Extracting\Strategies;
return [
    'title' => 'Trip Builder API',
//    'base_url' => 'http://localhost/api',
    'auth' => [
        'enabled' => true,
        'default' => true,
        'in' => 'bearer',
        'name' => 'hahaha', // <--- This value is ignored for bearer and basic auth
        'placeholder' => '{ACCESS_TOKEN}',
        'extra_info' => 'You can retrieve your token by visiting the **Auth** section of this documentation.',
        /*
         * The value of the parameter to be used by Scribe to authenticate response calls.
         * This will NOT be included in the generated documentation.
         * If this value is empty, Scribe will use a random value.
         */
        'use_value' => env('SCRIBE_AUTH_KEY'),
    ],
    'theme' => 'default',
    /*
     * A short description of your API. Will be included in the docs webpage, Postman collection and OpenAPI spec.
     */
    'description' => '',
    /*
     * The type of documentation output to generate.
     * - "static" will generate a static HTMl page in the /public/docs folder,
     * - "laravel" will generate the documentation as a Blade view, so you can add routing and authentication.
     */
    'type' => 'static',
    /*
     * Settings for `static` type output.
     */
    'static' => [
        /*
         * HTML documentation, assets and Postman collection will be generated to this folder.
         * Source Markdown will still be in resources/docs.
         */
        'output_path' => 'public/docs',
    ],
    /*
     * Settings for `laravel` type output.
     */
    'laravel' => [
        /*
         * Whether to automatically create a docs endpoint for you to view your generated docs.
         * If this is false, you can still set up routing manually.
         */
        'add_routes' => true,
        /*
         * URL path to use for the docs endpoint (if `add_routes` is true).
         * By default, `/docs` opens the HTML page, `/docs.postman` opens the Postman collection, and `/docs.openapi` the OpenAPI spec.
         */
        'docs_url' => '/docs',
        /*
         * Directory within `public` in which to store CSS and JS assets.
         * By default, assets are stored in `public/vendor/scribe`.
         * If set, assets will be stored in `public/{{assets_directory}}`
         */
        'assets_directory' => null,
        /*
         * Middleware to attach to the docs endpoint (if `add_routes` is true).
         */
        'middleware' => [],
    ],
    'try_it_out' => [
        /**
         * Add a Try It Out button to your endpoints so consumers can test endpoints right from their browser.
         * Don't forget to enable CORS headers for your endpoints.
         */
        'enabled' => true,
        /**
         * The base URL for the API tester to use (for example, you can set this to your staging URL).
         * Leave as null to use the current app URL (config(app.url)).
         */
        'base_url' => null,
        /**
         * Fetch a CSRF token before each request, and add it as an X-XSRF-TOKEN header. Needed if you're using Laravel Sanctum.
         */
        'use_csrf' => false,
        /**
         * The URL to fetch the CSRF token from (if `use_csrf` is true).
         */
        'csrf_url' => '/sanctum/csrf-cookie',
    ],
    /*
     * Text to place in the "Introduction" section, right after the `description`. Markdown and HTML are supported.
     */
    'intro_text' => <<<INTRO
This documentation aims to provide all the information you need to work with our API.

<aside>As you scroll, you'll see code examples for working with the API in different programming languages in the dark area to the right (or as part of the content on mobile).
You can switch the language used with the tabs at the top right (or from the nav menu at the top left on mobile).</aside>
INTRO
,
    /*
     * Generate a Postman collection (v2.1.0) in addition to HTML docs.
     * For 'static' docs, the collection will be generated to public/docs/collection.json.
     * For 'laravel' docs, it will be generated to storage/app/scribe/collection.json.
     * Setting `laravel.add_routes` to true (above) will also add a route for the collection.
     */
    'postman' => [
        'enabled' => true,
        /*
         * Manually override some generated content in the spec. Dot notation is supported.
         */
        'overrides' => [],
    ],
    /*
     * Generate an OpenAPI spec (v3.0.1) in addition to docs webpage.
     * For 'static' docs, the collection will be generated to public/docs/openapi.yaml.
     * For 'laravel' docs, it will be generated to storage/app/scribe/openapi.yaml.
     * Setting `laravel.add_routes` to true (above) will also add a route for the spec.
     */
    'openapi' => [
        'enabled' => true,
        /*
         * Manually override some generated content in the spec. Dot notation is supported.
         */
        'overrides' => [],
    ],
    /*
     * Custom logo path. This will be used as the value of the src attribute for the <img> tag,
     * so make sure it points to an accessible URL or path. Set to false to not use a logo.
     *
     * For example, if your logo is in public/img:
     * - 'logo' => '../img/logo.png' // for `static` type (output folder is public/docs)
     * - 'logo' => 'img/logo.png' // for `laravel` type
     *
     */
    'logo' => false,
    /**
     * Customize the "Last updated" value displayed in the docs by specifying tokens and formats.
     * Examples:
     * - {date:F j Y} => March 28, 2022
     * - {git:short} => Short hash of the last Git commit
     *
     * Available tokens are `{date:<format>}` and `{git:<format>}`.
     * The format you pass to `date` will be passed to PhP's `date()` function.
     * The format you pass to `git` can be either "short" or "long".
     */
    'last_updated' => 'Last updated: {date:F j, Y}',
    'examples' => [
        /*
         * If you would like the package to generate the same example values for parameters on each run,
         * set this to any number (eg. 1234)
         */
        'faker_seed' => null,
        /*
         * With API resources and transformers, Scribe tries to generate example models to use in your API responses.
         * By default, Scribe will try the model's factory, and if that fails, try fetching the first from the database.
         * You can reorder or remove strategies here.
         */
        'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
    ],
    'fractal' => [
        /* If you are using a custom serializer with league/fractal, you can specify it here.
         * Leave as null to use no serializer or return simple JSON.
         */
        'serializer' => null,
    ],
    /*
     * [Advanced] Custom implementation of RouteMatcherInterface to customise how routes are matched
     *
     */
    'routeMatcher' => \Knuckles\Scribe\Matching\RouteMatcher::class,
    /*
     * The base URL displayed in the docs. If this is empty, Scribe will use the value of config('app.url').
     */
    'base_url' => null,
];
