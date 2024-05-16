import hljs from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/highlight.min.js';
import twig from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/twig.min.js';
import xml from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/xml.min.js';
import php from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/php.min.js';
import javascript from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/javascript.min.js';

hljs.registerLanguage('twig', twig);
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('php', php);
hljs.registerLanguage('javascript', javascript);

export class Toolbar {
  constructor() {
    this.toolbar = document.querySelector('.toolbar');
    hljs.highlightAll();
  }

  bind() {
    document.addEventListener('nodenavigation', (e) => {
      console.log(e.detail)
    }, true)
  }
}
