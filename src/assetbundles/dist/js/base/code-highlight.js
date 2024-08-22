import hljs from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/highlight.min.js';
import twig from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/twig.min.js';
import xml from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/xml.min.js';
import scss from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/scss.min.js';
import php from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/php.min.js';
import javascript from 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/es/languages/javascript.min.js';
import * as prettier from 'https://unpkg.com/prettier@3.2.5/standalone.mjs';
import pluginBabel from 'https://unpkg.com/prettier@3.2.5/plugins/babel.mjs';
import pluginHtml from 'https://unpkg.com/prettier@3.2.5/plugins/html.mjs';
import pluginEstree from 'https://unpkg.com/prettier@3.2.5/plugins/estree.mjs';

// format code blocks with prettier and highlight.js
export function bindCodeHighlight(codeElements = []) {
  hljs.registerLanguage('twig', twig);
  hljs.registerLanguage('xml', xml);
  hljs.registerLanguage('php', php);
  hljs.registerLanguage('scss', scss);
  hljs.registerLanguage('javascript', javascript);
  codeElements.forEach(async (el) => {
    try {
      const lang = el.className.match(/.*language-(\w+)/)[1];
      el.textContent = await formatCode(el.textContent, lang)
      hljs.highlightElement(el, { language: lang });
    } catch (e) {
      console.warn('Failed to format code block')
    }
  })
}

// https://unpkg.com/browse/prettier@3.2.5/plugins/
async function formatCode(str, lang) {
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
