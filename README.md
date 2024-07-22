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
    ]
    'root' => dirname(__DIR__) . '/library',
    'docs' => dirname(__DIR__) . '/library/docs',
    'aliases' => [
        '@elements' => '02-elements',
        '@modules' => '03-modules',
    ]
];
```
