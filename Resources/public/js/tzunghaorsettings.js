document.addEventListener('DOMContentLoaded', () => {
    /**
     * Inputs whether to override a given setting in current scope (or use inherited value)
     * @type {NodeListOf<Element>}
     */
    const overrideInputs = document.querySelectorAll('.tzunghaor_setting_override input[type="radio"]');

    /**
     * Applies current override/inherit choice for the setting controlled by the passed Element
     * @param {HTMLInputElement} input
     */
    const applyOverride = function (input) {
        if (!input.checked) {
            return;
        }

        const enableInput = parseInt(input.value) === 1;

        const groupElement = input.closest('.tzunghaor_setting_group');
        if (enableInput) {
            groupElement.classList.remove('disabled');
        } else {
            groupElement.classList.add('disabled');
        }

        let inputElements = [];
        const inputs = groupElement.getElementsByTagName('INPUT');
        const selects = groupElement.getElementsByTagName('SELECT');
        const textareas = groupElement.getElementsByTagName('TEXTAREA');

        inputElements = Array.prototype.concat.apply(inputElements, inputs);
        inputElements = Array.prototype.concat.apply(inputElements, selects);
        inputElements = Array.prototype.concat.apply(inputElements, textareas);

        for (const inputElement of inputElements) {

            if (inputElement.closest('.tzunghaor_setting_override')) {
                continue;
            }

            inputElement.disabled = !enableInput;
        }
    };

    /**
     * Applies override/inherit setting and mark setting as changed
     * @param {Event} event
     */
    const onOverrideInputChange = function (event) {
        applyOverride(event.target);
        event.target.closest('.tzunghaor_setting_override').classList.add('tzunghaor_setting_changed');
    };

    // apply current override/inherit setting
    for (const input of overrideInputs) {
        applyOverride(input);
        input.addEventListener('change', onOverrideInputChange);
    }

    /**
     * Marks setting as changed when edited
     * @param {Event} event
     */
    const onValueChanged = function (event) {
        event.target.closest('.tzunghaor_setting_value').classList.add('tzunghaor_setting_changed');
    };

    for (const valueElement of document.querySelectorAll('.tzunghaor_setting_value')) {
        valueElement.addEventListener('change', onValueChanged);
    }

    /**
     * Sends POST request. Request and response body are expected to be an JSON encoded object
     *
     * @param {string} url
     * @param {object} data - will be sent in POST body JSON encoded
     * @param {function(object)} onload - receives the object returned in the response body
     *
     * @returns {XMLHttpRequest}
     */
    const postRequest = function (url, data, onload) {
        const xhttp = new XMLHttpRequest();
        xhttp.onload = function() {
            onload(JSON.parse(xhttp.response));
        }
        xhttp.open("POST", url, true);
        xhttp.setRequestHeader("Content-type", "application/json");
        xhttp.send(JSON.stringify(data));

        return xhttp;
    }

    // setting up AJAX scope search
    for (const searchInput of document.querySelectorAll('.tzunghaor_settings_scope_search')) {
        const containerElement = searchInput.closest('.tzunghaor_settings_scope_selector');
        const dataElement = containerElement.querySelector('.tzunghaor_settings_scope_selector_data');
        const scopeSearchConfig = JSON.parse(dataElement.textContent);
        const listTemplate = containerElement.querySelector('.tzunghaor_settings_scope_list_template').innerHTML;
        const scopeTemplate = containerElement.querySelector('.tzunghaor_settings_scope_template').innerHTML;
        const scopeListContainer = containerElement.querySelector('.tzunghaor_settings_scopes_list');

        /**
         * Builds html string from scopeHierarchy using the html templates in the document
         *
         * @param scopeHierarchy [{name: 'name', children: [...]}, ...]
         * @returns {string} html string
         */
        const buildScopeList = function(scopeHierarchy) {
            let list = '';
            for (const scope of scopeHierarchy) {
                let children = '';
                if (scope.hasOwnProperty('children')) {
                    children = buildScopeList(scope.children);
                }

                let scopeItem = scopeTemplate
                    .replace('%name%', scope.name)
                    .replace('%url%', scope.url)
                    .replace('%children%', children)
                    .replace(/%if\(iscurrent\){(.*?)}%/, scopeSearchConfig.currentScope === scope.name ? '$1' : '')
                ;

                list += scopeItem;
            }

            return listTemplate.replace('%list%', list);
        };

        /**
         * Scope search POST response handler
         * @param scopeHierarchy
         */
        const replaceScopeList = function(scopeHierarchy) {
            scopeListContainer.innerHTML = buildScopeList(scopeHierarchy);
        };


        /**
         * The current/last scope search request
         * @type {XMLHttpRequest}
         */
        let scopeSearchRequest = null;

        searchInput.addEventListener('keyup', function(event) {
            const searchData = {
                collection: scopeSearchConfig.collection,
                section: scopeSearchConfig.section,
                searchString: event.target.value,
                linkRoute: scopeSearchConfig.linkRoute,
            };
            // don't wait for previous request
            if (scopeSearchRequest) {
                scopeSearchRequest.abort();
            }
            scopeSearchRequest = postRequest(scopeSearchConfig.searchUrl, searchData, replaceScopeList);
        });
    }
});
