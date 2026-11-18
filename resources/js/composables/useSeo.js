import { watchEffect } from 'vue';
import { usePage } from '@inertiajs/vue3';

function setMeta(attribute, key, value) {
    if (!value) return;

    let element = document.head.querySelector(`meta[${attribute}="${key}"]`);

    if (!element) {
        element = document.createElement('meta');
        element.setAttribute(attribute, key);
        document.head.appendChild(element);
    }

    element.setAttribute('content', value);
}

function setLink(rel, href) {
    if (!href) return;

    let element = document.head.querySelector(`link[rel="${rel}"]`);

    if (!element) {
        element = document.createElement('link');
        element.setAttribute('rel', rel);
        document.head.appendChild(element);
    }

    element.setAttribute('href', href);
}

/**
 * Keeps the server-rendered SEO tags in sync during Inertia navigation.
 */
export function useSeo() {
    if (typeof document === 'undefined') return;

    const page = usePage();

    watchEffect(() => {
        const seo = page.props?.seo ?? {};

        if (seo.title) document.title = seo.title;
        if (seo.description) setMeta('name', 'description', seo.description);
        if (seo.robots) setMeta('name', 'robots', seo.robots);
        if (seo.canonical) setLink('canonical', seo.canonical);

        setMeta('property', 'og:title', seo.title);
        setMeta('property', 'og:description', seo.description);
        setMeta('property', 'og:url', seo.canonical);
        setMeta('property', 'og:image', seo.og_image);
        setMeta('name', 'twitter:title', seo.title);
        setMeta('name', 'twitter:description', seo.description);
    });
}
