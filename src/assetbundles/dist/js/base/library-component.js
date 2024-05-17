import { EventDispatcher } from './event-dispatcher.js';
import { Router } from './router.js';
const AppRouter = new Router();
const AppEvents = new EventDispatcher();

export class LibraryComponent extends EventDispatcher {
  constructor() {
    super();
    this.app = {
      router: AppRouter,
      events: AppEvents
    }
  }
}
