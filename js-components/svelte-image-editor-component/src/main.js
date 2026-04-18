import { mount, unmount } from 'svelte';
import './style.css';
import App from './App.svelte';

/**
 * Mount the Svelte app to a target element
 * @param {HTMLElement} target - DOM element to mount to
 * @param {Object} props - Props to pass to the component
 * @returns {Object} - Svelte component instance with $destroy method
 */
export function mountApp(target, props = {}) {
    const component = mount(App, {
        target,
        props
    });

    // Add $destroy method for compatibility with Oro view dispose
    component.$destroy = () => {
        unmount(component);
    };

    return component;
}

export default { mountApp };
