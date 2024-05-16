import { ExplorerTree } from './explorer-tree.js';
import { Preview } from './preview.js';
import { Toolbar } from './toolbar.js';
import { Splitter } from './splitter.js';

const splitter = new Splitter();
const sidebar = document.querySelectorAll('nav > details');
Array.from(sidebar).forEach(sidebar => new ExplorerTree(sidebar));
const preview = new Preview();
const toolbar = new Toolbar();

document.querySelector('.container').classList.remove('js-loading');
