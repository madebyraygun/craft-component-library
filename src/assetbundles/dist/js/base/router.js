import { EventDispatcher } from './event-dispatcher.js';

export class Router extends EventDispatcher {
  constructor() {
    super(document);
    this.baseUrl = new URL(document.body.dataset.baseUrl);
    this.bind();
  }

  bind() {
    this.addEventListener('click', (e) => {
      if (!this.maybeOverrideDefault(e)) return;
      e.preventDefault();
      this.onNavigationChange(e.target);
      this.updateState(e.target.href);
    }, true);
  }

  maybeOverrideDefault(e) {
    if (e.target.tagName !== 'A') return false;
    if (e.target.href.startsWith('#')) return false;
    if (e.target.target === '_blank') return false;
    const url = new URL(e.target.href);
    if (url.origin !== this.baseUrl.origin) return false;
    if (!url.href.startsWith(this.baseUrl.href)) return false;
    return true;
  }

  updateState(url) {
    window.history.pushState({}, '', url);
  }

  onNavigationChange(target) {
    const eventName = target.dataset.routerEvent || 'navigate';
    this.dispatchEvent(eventName, {
      bubbles: true,
      detail: {
        target: target,
        url: target.href
      }
    });
  }
}
