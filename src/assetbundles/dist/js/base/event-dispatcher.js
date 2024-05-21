export class EventDispatcher {
  constructor(element) {
    this.target = element || new EventTarget();
  }

  addEventListener(type, listener, options) {
    this.target.addEventListener(type, listener, options);
  }

  removeEventListener(type, listener, options) {
    this.target.removeEventListener(type, listener, options);
  }

  dispatchEvent(event, params = {}) {
    if (typeof event === 'string') event = new CustomEvent(event, params);
    return this.target.dispatchEvent(event);
  }
}
