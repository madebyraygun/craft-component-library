class EventDispatcher {
  constructor() {
    this.eventTarget = new EventTarget();
  }

  addEventListener(type, listener, options) {
    this.eventTarget.addEventListener(type, listener, options);
  }

  removeEventListener(type, listener, options) {
    this.eventTarget.removeEventListener(type, listener, options);
  }

  dispatchEvent(event) {
    return this.eventTarget.dispatchEvent(event);
  }
}
