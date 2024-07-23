import Fuse from 'https://cdn.jsdelivr.net/npm/fuse.js@7.0.0/dist/fuse.mjs'
import { LibraryComponent } from '../base/library-component.js';

export class SearchBar extends LibraryComponent {
  constructor(rootElement, items = []) {
    super();
    console.log(rootElement, items);
  }
}
