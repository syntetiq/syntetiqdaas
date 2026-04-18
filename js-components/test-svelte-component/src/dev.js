import { mountApp } from './main.js';

// Simulate the Oro application environment or passed props here
const props = {
    // Add any props your component expects
};

const app = mountApp(document.getElementById('app'), props);

export default app;
