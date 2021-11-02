class FetchError extends Error {
    name = 'Fetch Error';
}

if (typeof setListeners !== 'function') {
    function setAjaxListeners(getParams, cbSuccess) {
        const id = `${getParams('AJAX_COMPONENT_ID')}`;
        if (!id) return;
        const componentContainer = document.getElementById(id);
        if (!componentContainer) return;
        let aTags = [];

        function clickListener(event) {
            event.preventDefault();
            const href = new URL(this.href);
            href.searchParams.set(getParams('AJAX_PARAM_NAME'), getParams('AJAX_COMPONENT_ID'));
            //console.log('click\n', event.target, '\nthis: ', this);

            (async function () {
                const response = await fetch(href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) {
                    throw new FetchError(`${response.status} ${response.statusText}`);
                }
                const tmpDiv = document.createElement('div');
                tmpDiv.innerHTML = await response.text();
                Array.from(tmpDiv.children).forEach((el) => {
                    if (el.id === id) {
                        href.searchParams.delete(getParams('AJAX_PARAM_NAME'));
                        window.history.replaceState(null, '', href.toString());
                        aTags.forEach((aElement) => {
                            aElement.removeEventListener('click', clickListener);
                        });
                        componentContainer.innerHTML = el.innerHTML;
                        tmpDiv.remove();
                        setAjaxListeners(getParams, cbSuccess);
                        typeof cbSuccess === 'function' && cbSuccess();
                        return false;
                    }
                });
            })();
        }

        Array.from(componentContainer.querySelectorAll('*[data-ajax-links] a')).forEach((el) => {
            const href = new URL(el.href);
            if (href.pathname === window.location.pathname) {
                aTags.push(el);
                href.searchParams.delete(getParams('AJAX_PARAM_NAME'));
                el.setAttribute('href', href.toString());
                el.addEventListener('click', clickListener);
            }
        });
    }
}
