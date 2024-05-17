import hljs from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/highlight.min.js';
import twig from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/twig.min.js';
import xml from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/xml.min.js';
import php from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/php.min.js';
import javascript from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/javascript.min.js';
import * as prettier from 'https://unpkg.com/prettier@3.2.5/standalone.mjs';
import pluginBabel from 'https://unpkg.com/prettier@3.2.5/plugins/babel.mjs';
import pluginHtml from 'https://unpkg.com/prettier@3.2.5/plugins/html.mjs';
import pluginEstree from 'https://unpkg.com/prettier@3.2.5/plugins/estree.mjs';

hljs.registerLanguage('twig', twig);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('php', php);
hljs.registerLanguage('javascript', javascript);

export class Toolbar {
  constructor() {
    this.toolbar = document.querySelector('.toolbar');
    this.initializeCodeHighlighting();
  }

  // format code blocks with prettier and highlight.js
  initializeCodeHighlighting() {
    const codeElements = this.toolbar.querySelectorAll('pre code[class^="language-"]');
    codeElements.forEach(async (el) => {
      const lang = el.className.match(/language-(\w+)/)[1];
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
    })).replace(/;\s$/, '')
  }

  bind() {
    document.addEventListener('nodenavigation', (e) => {
      console.log(e.detail)
    }, true)
  }
}
