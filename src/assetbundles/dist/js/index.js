import { Sidebar } from './sidebar.js';
import { Preview } from './preview.js';
import { Toolbar } from './toolbar.js';
import { Splitter } from './splitter.js';

const splitter = new Splitter();
const sidebar = new Sidebar();
const preview = new Preview();
const toolbar = new Toolbar();

document.querySelector('.container').classList.remove('js-loading');
