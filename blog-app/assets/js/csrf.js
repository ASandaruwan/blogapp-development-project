// Global CSRF Token Fetch Interceptor
(function() {
    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    // Attach token to window
    window.getCSRFToken = getCsrfToken;

    // Intercept standard fetch requests to auto-append CSRF token
    if (typeof window.fetch !== 'undefined') {
        const nativeFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            options = options || {};
            const method = (options.method || 'GET').toUpperCase();

            if (['POST', 'PUT', 'DELETE', 'PATCH'].includes(method)) {
                const token = getCsrfToken();
                if (token) {
                    if (options.headers instanceof Headers) {
                        if (!options.headers.has('X-CSRF-TOKEN')) {
                            options.headers.set('X-CSRF-TOKEN', token);
                        }
                    } else if (Array.isArray(options.headers)) {
                        options.headers.push(['X-CSRF-TOKEN', token]);
                    } else {
                        options.headers = options.headers || {};
                        if (!options.headers['X-CSRF-TOKEN'] && !options.headers['x-csrf-token']) {
                            options.headers['X-CSRF-TOKEN'] = token;
                        }
                    }
                }
            }
            return nativeFetch(url, options);
        };
    }
})();
