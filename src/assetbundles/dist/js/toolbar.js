import hljs from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/highlight.min.js';
import twig from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/twig.min.js';
import xml from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/xml.min.js';
import php from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/php.min.js';
import javascript from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/javascript.min.js';
import * as prettier from 'https://unpkg.com/prettier@3.2.5/standalone.mjs';
import pluginBabel from 'https://unpkg.com/prettier@3.2.5/plugins/babel.mjs';
import pluginHtml from 'https://unpkg.com/prettier@3.2.5/plugins/html.mjs';
import pluginEstree from 'https://unpkg.com/prettier@3.2.5/plugins/estree.mjs';
import { LibraryComponent } from './base/library-component.js';

export class Toolbar extends LibraryComponent {
  constructor() {
    super();
    this.toolbar = document.querySelector('.toolbar');
    this.bindCodeHighlighting();
    this.bindCodeSwitches();
  }

  bindCodeSwitches() {
    const switches = this.toolbar.querySelectorAll('.code-switch');
    switches.forEach(el => {
      this.updateCodeMode(el);
      el.addEventListener('change', (e) => {
        this.updateCodeMode(e.target.closest('.code-switch'))
      }, true)
    })
  }

  updateCodeMode(switchElement) {
    const checked = switchElement.querySelector('input').checked;
    const pane = switchElement.closest('.tabs__pane');
    const code = pane.querySelector('.toolbar__code');
    code.classList.toggle('toolbar__code--compile-enabled', checked);
  }

  // format code blocks with prettier and highlight.js
  bindCodeHighlighting() {
    hljs.registerLanguage('twig', twig);
    hljs.registerLanguage('xml', xml);
    hljs.registerLanguage('php', php);
    hljs.registerLanguage('javascript', javascript);
    const codeElements = this.toolbar.querySelectorAll('pre code');
    codeElements.forEach(async (el) => {
      const lang = el.className.match(/.*language-(\w+)/)[1];
      el.textContent = await this.formatCode(el.textContent, lang)
      hljs.highlightElement(el, { language: lang });
    })
  }

  // https://unpkg.com/browse/prettier@3.2.5/plugins/
  async formatCode(str, lang) {
    const config = {
      /**
       * Only format compiled output languages
       * we don't want to format the source code
       */
      'html': { parser: 'html' },
      'json': { parser: 'json' },
    }
    if (!config[lang]) return str;
    return (await prettier.format(str, {
      parser: config[lang].parser || lang,
      plugins: [pluginBabel, pluginEstree, pluginHtml],
    })).replace(/;\s$/, '');
  }

  bind() {

  }
}
