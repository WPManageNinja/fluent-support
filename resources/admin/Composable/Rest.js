const request = function (method, route, data = {}) {
    const url = `${window.fluentSupportAdmin.rest.url}/${route}`;

    const headers = {'X-WP-Nonce': window.fluentSupportAdmin.rest.nonce};

    if (['PUT', 'PATCH', 'DELETE'].indexOf(method.toUpperCase()) !== -1) {
        headers['X-HTTP-Method-Override'] = method;
        method = 'POST';
    }

    return window.jQuery.ajax({
        url: url,
        type: method,
        data: data,
        headers: headers
    });
}

export function useRestApi() {
    function get(route, data) {
        return request('GET', route, data)
    }

    function post(route, data) {
        return request('POST', route, data)
    }

    function del(route, data) {
        return request('DELETE', route, data);
    }

    function put(route, data) {
        return request('PUT', route, data);
    }

    function patch(route, data) {
        return request('PATCH', route, data);
    }

    return {
        get,
        post,
        del,
        put,
        patch
    }
}


jQuery(document).ajaxSuccess((event, xhr, settings) => {
    if (!window.fluentSupportAdmin) {
        return;
    }

    const nonce = xhr.getResponseHeader('X-WP-Nonce');
    if (nonce) {
        window.fluentSupportAdmin.rest.nonce = nonce;
    }
});



