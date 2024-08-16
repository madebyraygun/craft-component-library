import { LibraryComponent } from './base/library-component.js';
import 'https://cdnjs.cloudflare.com/ajax/libs/split.js/1.6.0/split.min.js';
/**
 * Uses https://github.com/nathancahill/split/tree/master/packages/splitjs
 */

export class Splitter extends LibraryComponent {
  constructor() {
    super();
    this.splitters = [];
    this.createSplitters();
    this.bindEvents();
  }

  createSplit(pair, options = {}) {
    const exists = document.querySelector(pair[0]).parentElement.querySelectorAll('.gutter');
    if (exists.length > 0) return;
    this.splitters.push(Split(pair, options));
  }

  createSplitters() {
    this.createSplit([
      '#split-sidebar',
      '#split-content',
    ], {
      direction: 'horizontal',
      sizes: [20, 80],
      minSize: 300,
      gutterSize: 8,
      cursor: 'col-resize',
    })
    this.createSplit([
      '#split-preview',
      '#split-toolbar',
    ], {
      direction: 'vertical',
      sizes: [60, 40],
      gutterSize: 8,
    })
    this.createSplit([
      '#preview-iframe',
      '#split-preview-resize'
    ], {
      direction: 'horizontal',
      minSize: 0,
      sizes: [100, 0],
      gutterSize: 8,
      onDrag: function () {
        console.log('dragging')
      },
    })
  }

  bindEvents() {
    this.app.events.addEventListener('toolbar-visibility-changed', (e) => {
      if (e.detail.visible) {
        setTimeout(() => {
          this.createSplitters();
        }, 100);
      }
    })
  }
}
