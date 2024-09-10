# component-library

Component Library to preview and develop integrated components with CraftCMS

## Requirements

This plugin requires Craft CMS 5.0.0 or later, and PHP 8.2 or later.


## Configuration
Create a `component-library.php` file in your Craft config directory

Sample config:
```php
return [
    'browser' => [
      'enabled' => true,
      'requiresLogin' => true,
      'path' => 'my-library',
      'welcome' => '@docs/index',
      'preview' => '@preview-template',
    ]
    'root' => dirname(__DIR__) . '/library',
    'docs' => dirname(__DIR__) . '/library/docs',
    'aliases' => [
        '@elements' => '02-elements',
        '@modules' => '03-modules',
    ]
];
```

- **`browser.enabled`** - <default: `true`>. Enables/disables completely the component library browser. Global usage for your components inside the library are not affected.
- **`browser.requiresLogin`** <default: `false`>. Requires the user to be logged in to access the component library browser. The Browser needs to be enabled for this to work.
- **`browser.path`** <default: `component-library`>. The path to access the component library browser from the site root.
- **`browser.welcome`** <default: `''`> The markdown component handle to display as the welcome page.
- **`browser.preview`** <default: `@preview`> This is the default template to use as the preview on all components that do not have a specific preview template in their settings.
- **`root`** <default: `'@root/library'`> The root directory of the component library.
- **`docs`** <default: `'@root/library/docs'`> The directory where the markdown files are stored.
- **`aliases`** <default: `[]`> The aliases for the directories inside the component library. This is used to make custom shorthandles for the directories.


## Components
Library components are just `twig` templates that are stored in the component library directory. The component library browser will automatically detect and make them available anywhere in your craft templates.

For example, if you have a directory `library/button` that contains a `button.twig` file you can include it in your templates using the `@component` alias. The directory structure would look like:
```
.
└── library/
    └── button/
        └── button.twig
```
And you would include it in your templates using its handle:
```twig
  {% include '@button/button' %}
```

## Aliases and Handles
The path of each component inside the library is what defines its handle.
So for the following directory structure:
```
.
└── library/
    └── elements/
        └── button/
            └── button.twig
```
You could include the `button` component using the full relative path:
```twig
  {% include '@elements/button/button' %}
```

But for more complex structures it can be useful to define aliases for directories. This is done in the `component-library.php` config file. For example, if you have a directory `elements/button` that you want to access as `@button` you would an alias as:
```php
'aliases' => [
    '@button' => 'elements/button',
]
```
Then you can include it using:
```twig
  {% include '@button/button' %}
```

### Collapsed Form Handles
Additionally when a parent directory has the same name as the component (case sensitive) you can use a shorter handle (collapsed form) to include the component. So for the example above you could include the button component just by using:
```twig
  {# looks for ~/elements/button/button.twig (using config alias example) #}
  {% include '@button' %}
```

## Component Variants
Components can define multiple versions of themselves by naming the files with a double dash suffix. So the following directory structure:
```
.
└── library/
    └── button/
        └── button.twig
        └── button--primary.twig
```
Would allow you to include the button component and its primary variant using:
```twig
  {% include '@button/button' %}
  {% include '@button/button--primary' %}

  {# or using the shorter handle #}
  {% include '@button' %}
  {% include '@button--primary' %}
```
But you can always define a totally different path structure for your components library like:
```
.
└── library/
    └── toolbar/
        └── button.twig
        └── dropdown.twig
        └── dropdown--small.twig
```
And include them using:
```twig
  {% include '@toolbar/button' %}
  {% include '@toolbar/dropdown' %}
  {% include '@toolbar/dropdown--small' %}
```

## Project Documentation
The component library can also display markdown files as documentation for your components. By default the markdown files goes inside the `~/docs` directory of your component library. Browser will automatically detect and make them available using the same directory structure. All `*.md` files are parsed with the [Craft Markdown Filter](https://craftcms.com/docs/5.x/reference/twig/filters.html#markdown-or-md) using the `gfm` flavor. See full sintax and formatting in the [docs](https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax).

Additionally you can use any of the markdown files as the welcome page for the component library browser by setting the `browser.welcome` config (see [Configuration](#configuration)).

## Component Documentation
Each component can have its own documentation by creating a markdown file with the same name as the component. For example, if you have a `button` component you can create a `button.md` file next to the `button.twig` file. The markdown file will be parsed and displayed in the toolbar tab of the component preview.

Example:
```
.
└── library/
    └── button/
        └── button.twig
        └── button.md
```

## Component Configuration and Context
Each component can have its own configuration file to define its context and preview template. The configuration file provides a way to define the `context` of the component and the `preview` template to use when displaying the component in the browser among other things.

The purpose of the `context` is to enable the component to be developed and previewed in isolation without the need of real data comming from the CMS. When developing in the library a component is provided with a mock data object that can be used to preview the component in different states.

To create a configuration file for a component you need to create a `*config.php` or a `*config.json` file next to the component template file using the same name as the component. Like so:
```
.
└── library/
    └── button/
        └── button.twig
        └── button.config.php
```
Inside the configuration file you can define the following properties:
```php
return [
  'title' => 'Button Title', // Custom title for the component
  'hidden' => false, // Hides the component
  'preview' => '@preview', // The template to render the component
  'context' => [
    'text' => 'Lorem Ipsum Dolor',
    'type' => 'button',
  ],
]
```
After defining the config file you can make use of the `context` object in the component template like so:
```twig
  <button type="{{type}}">
    {{text}}
  </button>
```

If you have variants of the component you can define the settings and the context for each variant like so:
```php
return [
  'title' => 'Button Title',
  'hidden' => false,
  'preview' => '@preview',
  'context' => [
    'text' => 'Lorem Ipsum Dolor',
    'type' => 'button',
  ],
  'variants' => [
    'title' => 'Variant Title',
    'context' => [
        'text' => 'Custom Text',
        'class' => 'red'
    ]
  ]
]
```

Variants will inherit the context from the *root* component when not defined. So this means that in the example above the `type` property will be available in the variant context.

Additionally you can reference other components context by using the `@component` alias like so:
```php
return [
  'context' => [
    'button' => '@button',
    'class' => '@button--variant.class',
  ]
]
```
This will make the `button` and `class` properties available in the context object of the component context. This is useful when you need to make use of other components context in your component. The final (compiled) form of the context object for the example above would be:
```php
return [
  'context' => [
    'button' => [
      'text' => 'Lorem Ipsum Dolor',
      'type' => 'button',
    ],
    'class' => 'red',
  ]
]
```

When using `php` configuration files you are free to use any PHP code to define the context object in more complex ways. We find useful to include a library to mock data like [Faker](https://fakerphp.org/).

## Previews
The library browser will render the components preview using the `preview` template defined in the component configuration file. If no preview template is defined the default preview template will be used. The preview template is a regular `twig` file declared in the library. You would normally define a preview template to use your components under a single folder like so:
```
.
└── library/
    └── button/
        └── button.twig
        └── button.config.php
    └── preview/
        └── preview.twig
        └── preview--dark.twig
```

In `button.config.php` you would define the preview template to use like so:
```php
return [
  'preview' => '@preview',
  'context' => ...
]
```

and the preview is normally the base layout HTML wrapper for your components like so:
```twig
<!DOCTYPE html>
<html>
  <head>
    <title>Component Preview</title>
  </head>
  <body>
    <main>
      {% block main %}
        {{ yield|raw }}
      {% endblock %}
    </main>
  </body>
</html>
```

Although you will normally want to use the actual site layout to preview your components. You can do this by customizing the contents of your `preview.twig` like so:
```twig
{% extends "_layouts/_site" %}
{% block main %}
  {{ yield|raw }}
{% endblock %}
```
Where `{{yield|raw}}` will be replaced with the HTML ouput of the component being rendered and `_layouts/_site` is the path under your `craft/templates` directory.

Since previews are only meant to be used indirectly by each component there is no need to have them accessible from the browser. You can hide them by setting the `hidden` property in the configuration file like so:
```php
return [
  'hidden' => true,
  'context' => ...
]
```

## Vite Integration
The component library comes with full Vite support. The plugin internally uses [nystudio107/craft-vite](https://nystudio107.com/docs/vite/) to provide a seamless integration with the Vite build system. This allows you to use the latest ES6+ features, SCSS, PostCSS, and more in your components. If you want to see the Vite integration in action you can check the [Vite Example](htts://github.com) repository.

### DDEV Configuration
To use Vite with `DDEV` you'll need to expose the ports by adding the following lines into your `ddev.yaml` file. (Dev environment only):
```yaml
web_extra_exposed_ports:
    - name: node-vite
      container_port: 8080
      http_port: 8081
      https_port: 8080
```
Remember that if you are using `Vite` with `DDEV` or a custom `Docker` container you will need to start the server **inside** the container and not in your host machine. For Vite this would look like:
```bash
ddev exec npm run dev
```
