Release Notes for Component Library plugin

## Unreleased

### Changed
- `browser.enabled` and `browser.requiresLogin` now read strings such as `'false'` and `'$VARIABLE'` references as booleans; before, any non-empty string except `'0'` counted as `true` (#50)

### Fixed
- `browser.enabled` set to `false` now applies to every route of the library browser (#50)
- README corrected: `browser.requiresLogin` defaults to `true` (#50)

## 1.1.0 - 2026-02-26

### Added
- Search bar with keyboard shortcut for quick component lookup (#25)
- Support for referencing nested context in component configs (#36)
- Configurable preview component handle setting (#38)
- Markdown documentation tab for components (#17)
- Resizable preview pane splitter (#31)
- Welcome page and error page styling (#30)
- Library browser icon (#28)
- Option to disable the component library browser (#18)
- Hidden components and directories via config (#15)
- Virtual component variants (#15)
- Craft CMS 4.x compatibility (#23)

### Fixed
- Wrong prop name on exception handler
- Plugin init log removed on startup
- Various stability fixes for preview rendering and toolbar loading

### Performance
- Lazy load component context during tree building, significantly improving load times for large component libraries (#49)

## 1.0.0 - 2024-09-16

First public release.
