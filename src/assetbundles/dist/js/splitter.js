console.log('splitter!');
(function() {
  Split([
    '#split-sidebar',
    '#split-preview',
  ], {
    sizes: [20, 80],
    minSize: 200,
    gutterSize: 20,
  })
  Split([
    '#split-render',
    '#split-toolbar',
  ], {
    sizes: [60, 40],
    gutterSize: 20,
  })
})()
