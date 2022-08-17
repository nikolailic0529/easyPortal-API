<?php declare(strict_types = 1);

use Stevebauman\Purify\Definitions\Html5Definition;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Config
    |--------------------------------------------------------------------------
    |
    | This option defines the default config that are provided to HTMLPurifier.
    |
    */

    'default'     => 'default',

    /*
    |--------------------------------------------------------------------------
    | Config sets
    |--------------------------------------------------------------------------
    |
    | Here you may configure various sets of configuration for differentiated use of HTMLPurifier.
    | A specific set of configuration can be applied by calling the "config($name)" method on
    | a Purify instance. Feel free to add/remove/customize these attributes as you wish.
    |
    | Documentation: http://htmlpurifier.org/live/configdoc/plain.html
    |
    |   Core.Encoding               The encoding to convert input to.
    |   HTML.Doctype                Doctype to use during filtering.
    |   HTML.Allowed                The allowed HTML Elements with their allowed attributes.
    |   HTML.ForbiddenElements      The forbidden HTML elements. Elements that are listed in this
    |                               string will be removed, however their content will remain.
    |   CSS.AllowedProperties       The Allowed CSS properties.
    |   AutoFormat.AutoParagraph    Newlines are converted in to paragraphs whenever possible.
    |   AutoFormat.RemoveEmpty      Remove empty elements that contribute no semantic information to the document.
    |
    */

    'configs'     => [

        'default' => [
            'Core.Encoding'            => 'utf-8',
            'HTML.Doctype'             => 'HTML 4.01 Transitional',
            'HTML.Allowed'             => 'p[class],strong,em,u,s,ol,li[class],ul,br',
            'Attr.AllowedClasses'      => implode(',', [
                'ql-align-center',
                'ql-align-right',
                'ql-align-justify',
                'ql-indent-1',
                'ql-indent-2',
                'ql-indent-3',
                'ql-indent-4',
                'ql-indent-5',
                'ql-indent-6',
                'ql-indent-7',
                'ql-indent-8',
            ]),
            'HTML.ForbiddenElements'   => '',
            'CSS.AllowedProperties'    => '',
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty'   => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | HTMLPurifier definitions
    |--------------------------------------------------------------------------
    |
    | Here you may specify a class that augments the HTML definitions used by
    | HTMLPurifier. Additional HTML5 definitions are provided out of the box.
    | When specifying a custom class, make sure it implements the interface:
    |
    |   \Stevebauman\Purify\Definitions\Definition
    |
    | Note that these definitions are applied to every Purifier instance.
    |
    | Documentation: http://htmlpurifier.org/docs/enduser-customize.html
    |
    */

    'definitions' => Html5Definition::class,

    /*
    |--------------------------------------------------------------------------
    | Serializer location
    |--------------------------------------------------------------------------
    |
    | The location where HTMLPurifier can store its temporary serializer files.
    | The filepath should be accessible and writable by the web server.
    | A good place for this is in the framework's own storage path.
    |
    */

    'serializer'  => storage_path('app/purify'),

];
