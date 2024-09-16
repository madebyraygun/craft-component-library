# Component Library plugin for Craft CMS

A full-featured component library system for building, previewing, and maintaining front-end web components, fully integrated with [Craft CMS](https://craftcms.com). Component preview data can be hard-coded via config file, dynamically generated with PHP, or populated directly from live Craft entries.

The browser-based library viewer allows you to traverse or search your component folder and preview each component, as well as displaying helpful details about the raw and compiled template, context data, and documentation.

The plugin is CSS and Javascript-framework agnostic, and can support any folder structure, including nested, hierarchical component structures to support an [Atomic Design](https://atomicdesign.bradfrost.com) methodology.

This plugin works hand-in-hand with the [Block Loader](https://github.com/madebyraygun/craft-block-loader) plugin to format and populate block data in your components. See an example of both plugins in action in the [Component Library Demo](https://github.com/madebyraygun/craft-component-library-demo) repository.

## Requirements

This plugin requires Craft CMS 4.10.0 or later, and PHP 8.2 or later.

This plugin can work with any front-end build system (webpack, mix, etc), but uses [Plugin Vite](https://github.com/nystudio107/craft-plugin-vite) under the hood. A fully functional demo using Vite on the front-end can be found in the [Component Library Demo](https://github.com/madebyraygun/craft-component-library-demo/blob/dev/README.md#vite-configuration) repo.


## Configuration

Optionally create a `component-library.php` file in your Craft `config` directory.

Sample config:

```php
return [
    'browser' => [
      'enabled' => true,
      'requiresLogin' => false,
      'path' => 'component-library',
      'welcome' => '@docs/index',
      'preview' => '@preview',
    ]
    'root' => dirname(__DIR__) . '/library',
    'docs' => dirname(__DIR__) . '/library/docs',
    'aliases' => [
        '@elements' => '02-elements',
        '@modules' => '03-modules',
    ]
];
```

- **`browser.enabled`** - <default: `true`>. Enables or disables the component library browser. This disables the front-end standalone viewer for components only, template loading is still functional.
- **`browser.requiresLogin`** <default: `false`> Requires user login to access the component library browser. The browser must be enabled for this setting to take effect.
- **`browser.path`** <default: `component-library`> The URL path for accessing the component library browser from the site root.
- **`browser.welcome`** <default: `''`> The handle of the markdown component to display as the welcome page.
- **`browser.preview`** <default: `@preview`> Default template to use as the preview on all components that do not have a specific preview template in their settings. See [Previews](#previews) for more information.
- **`root`** <default: `'library'`> The root directory of the component library, default is a folder called `library` in the `craft` application folder.
- **`docs`** <default: `'@root/docs'`> The directory where the markdown files are stored.
- **`aliases`** <default: `[]`> Custom shorthand aliases for directories inside the component library. See [Aliases and handles](#aliases-and-handles) for more information.


## Components

Components are simply folders in your component library directory that contain a `twig` template file and other related files. The component library browser will automatically detect and make these components available anywhere in your Craft templates.

A component folder must contain a `twig` template file, and typically also includes a context file (`*.config.json` or `*.config.php`) with the configuration information and context data to populate the template. Component folders also often include `.css` or `.scss` files for styling, `.js` or `.ts` files for interactivity, and may include a `readme.md` file to document usage.

Components can include or extend other components and their data, offering the ability to compose more complex components and even full layouts within the library browser. A simple example might be a single `card` component, which includes a `heading` component and an `image` component. The card component might then be included by a `cards` group, which is then included in a `blog` page layout.

___ 

*Note: The plugin is front-end agnostic and is not responsible for compiling the component CSS or Javascript of your components. Any front-end framework and build-system can be supported. We've included an example Vite immplementation in the [demo repository](https://github.com/madebyraygun/craft-component-library-demo/blob/dev/README.md#vite-configuration).*

___ 

The handle for each component is defined by the path of the component inside the library.

Example:

If you have a `button` component in the following directory:
```
.
└── library/
    └── button/
        └── button.twig
```
You can include it in your Craft templates by using:
```twig
  {% include '@button/button' %}
```

If a component directory and filename are identical (case sensitive), you can use a shortened form of the handle:

```twig
  {# looks for ~/button/button.twig  #}
  {% include '@button' %}
```

### Aliases and Handles

When dealing with nested directories or long handles it can be useful to define aliases to shorter handles.

Example:
With this structure:
```
.
└── library/
    └── elements/
        └── button/
            └── button.twig

```
Instead of using the full path:
```twig
  {% include '@elements/button/button' %}
```

You can define an alias in `component-library.php`:
```php
'aliases' => [
    '@button' => 'elements/button',
]
```
Now, include the component like this:
```twig
  {# looks for ~/elements/button/button.twig  #}
  {% include '@button/button' %}
```

And in fact, the shortened handle of this component is simply:

```twig
  {# looks for ~/elements/button/button.twig  #}
  {% include '@button' %}
```

___ 

Another use case for aliases to is force the sort order of top-level folders, for example when implementing an atomic design folder hierarchy. For example, our folder names might look like: `01-atoms`, `02-molecules`, `03-organisms`

With this folder structure:
```
.
└── library/
    └── 01-atoms/
        └── button/
            └── button.twig
```

By adding aliases to our config:
```php
'aliases' => [
    '@atoms' => '01-atoms',
    '@molecules' => '02-molecules',
    '@organisms' => '03-organisms'
]
```

You can now call the button component with:

```twig
  {% include '@atoms/button' %}
```

## Component Configuration and Context

Each component will have its own configuration file to define its settings and context.

The purpose of the `context` is to enable the component to be developed and previewed in isolation without the need of real data comming from the CMS. When developing in the library a component is provided with a mock data object that can be used to preview the component in different states.

To create a configuration file for a component you need to create a `config.php` or a `config.json` file in the same folder and using the same name as the component. 

A simple example might look like this:

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
  'title' => 'Button', // Custom title for the component
  'hidden' => false, // Hides the component
  'preview' => '@preview', // The template to render the component
  'context' => [
    'text' => 'Lorem Ipsum Dolor',
    'class' => 'is-primary',
  ],
]
```

After defining the config file you can make use of the `context` object in the component template like so:

```twig
  <button class="{{class|default('is-primary')}}">
    {{text|default('click me')}}
  </button>
```

## Component Variants

Components often have multiple implementations, called variants. For example, a single button component might have a `primary` and a `secondary` variant. Variants can also useful for testing  focus or disabled states. Variants can be defined through context, additional template files, or *both*. Let's try a couple of examples.

### File-based variants
Create a file-based variant by adding a new `twig` file with the same name and a double dash suffix. Given the following directory structure:

```
.
└── library/
    └── button/
        └── button.twig
        └── button--primary.twig
```

You can include the button component and its primary variant using:

```twig
  {% include '@button/button' %}
  {% include '@button/button--primary' %}

  {# or using the shorter handle #}
  {% include '@button' %}
  {% include '@button--primary' %}
```

You can also define a different path structure for your components library like:
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

### Context-based variants

Context-based variants are defined by an additional `variants` array in your context definition. Here you can see we've added multiple heading levels to `heading.config.json`:
```json
{
  "name": "Heading",
  "hidden": false,
  "context": {
    "level": "h1",
    "class": "heading-1",
    "content": "Lorem Ipsum Dolor Sit Amet"
  },
  "variants": [
    {
      "name": "H2",
      "context": {
        "level": "h2",
        "class": "heading-2"
      }
    },
    {
      "name": "H3",
      "context": {
        "level": "h3",
        "class": "heading-3"
      }
    }
  ]
}
```

Variants will inherit the context from the *root* component when not defined. So this means that in the example above the `content` property will be available in the variant contexts.

### Referencing other components

Component template files can reference one another:

```
.
└── library/
    └── button/
        └── button.twig
        └── button.config.php
    └── cta/
        └── cta.twig
        └── cta.config.php
```

`cta.twig`:
```twig
<div class="cta">
  <p>{{ctaText}}</p>
  {% include '@button' with button %}
</div>
```


Additionally you can reference other components context by using the component alias like so:

`cta.config.php`:
```php
return [
  'context' => [
    'ctaText' => 'Lorem ipsum dolor sit amet',
    'button' => '@button', //pulls in the entire @button context
  ]
]
```

or
```php
return [
  'context' => [
    'ctaText' => 'Lorem ipsum dolor sit amet',
    'button' => [
      'text' => 'Custom text',
      'class' => '@button.class' //pulls in just class value from the button component 
    ]
  ]
]
```

When using `php` configuration files you are free to use any PHP code to define the context object in more complex ways. We find useful to include a library to mock data like [Faker](https://fakerphp.org/). Here's an example for a Heading component with multiple variants, all populated with content generated by Faker:

```php
<?php 

use Faker\Factory as Faker;
$faker = Faker::create();

$tagClassMap = [
	'h1' => 'heading-1',
	'h2' => 'heading-2',
	'h3' => 'heading-3',
	'h4' => 'heading-4',
];

$variants = [];

foreach ($tagClassMap as $level => $class) {
	$variants[] = [
		'name' => $level,
		'context' => [
			'level' => $level,
			'class' => $class,
			'text' => $faker->sentence()
		]
	];
}

$first = array_shift($variants);

return [
	'name' => $first['name'],
	'context' => $first['context'],
	'variants' => $variants
];
```

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
    {# if using built-in Vite dev server #}
    {{ craft.library.script("src/app.js") }} 
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

You may also want to use your live site layout to preview your components. You can do this by customizing the contents of your `preview.twig` like so:

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

## Component Library documentation 

You can document your component library at the root project level as well as the individual component level.

### Project documentation

Project documentation can be added as a folder of markdown files in the `/docs` directory of your component library folder. The library browser will automatically detect and make these files available. All `*.md` files are parsed with the [Craft Markdown Filter](https://craftcms.com/docs/5.x/reference/twig/filters.html#markdown-or-md) using the `gfm` flavor. See full syntax and formatting in the [docs](https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax).

Additionally you can use any of the markdown files as the welcome page for the library browser by setting the `browser.welcome` config (see [Configuration](#configuration)).

## Component documentation

Each component can have its own documentation by creating a markdown file with the same name as the component. For example, if you have a `button` component you can create a `button.md` file next to the `button.twig` file. The markdown file will be parsed and displayed in the toolbar tab of the component preview.

Example:
```
.
└── library/
    └── button/
        └── button.twig
        └── button.md
```

## Vite Integration

The component library includes full Vite support. The plugin internally uses [nystudio107/craft-vite](https://nystudio107.com/docs/vite/) to provide a seamless integration with the Vite build system. This allows you to use the latest ES6+ features, SCSS, PostCSS, and more in your components. See our [sample Vite implementation](https://github.com/madebyraygun/craft-component-library-demo/blob/dev/README.md#vite-configuration) in the Component Library Demo repo.

## Using your components in Craft

It's possible to load your components directly into your Craft twig templates by passing along the necessary data, like so:

```html
{% include '@modules/card/article with {url: '#', title: 'Card title'} %}
```

This can get tiresome when working with more complex groups of components or when data needs additional formatting. We prefer to use Craft's [template hooks](https://craftcms.com/docs/5.x/extend/template-hooks.html) to format data and inject into the entry context. This can be done by creating a custom module:

`craft/modules/pagecontext/components/HomepageEntryContext.php`

```php
  
namespace modules\pagecontext\components;

use Craft;

class HomepageEntryContext
{

  public static function Hook() 
  {

    Craft::$app->view->hook('homepage-entry-context', function(array &$context) {
    $context['homepage_entry_context'] = [
      'banner' => self::GetBanner($context['entry']),
      'pageContent' => self::GetPageContent($context['entry'])
    ];
  });
}
...
```

`craft/templates/homepage/entry.twig`
```twig
{% hook 'homepage-entry-context' %}
{% extends "_layouts/_site" %}
{% block main %}
  {% include '@modules/homepageBanner' with homepage_entry_context.banner %}
  {% include '@modules/pageContent' with homepage_entry_context.pageContent %}
{% endblock %}
```

More examples are included in the [Component Library Demo](https://github.com/madebyraygun/craft-component-library-demo) repository. We've also included examples using the [Block Loader](https://github.com/madebyraygun/craft-block-loader) plugin to format and populate more complex matrix block data.
