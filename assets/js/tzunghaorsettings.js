document.addEventListener('DOMContentLoaded', () => {
    /**
     * Inputs whether to override a given setting in current scope (or use inherited value)
     * @type {NodeListOf<Element>}
     */
    const overrideInputs = document.querySelectorAll('.tzunghaor_setting_override input[type="radio"]');

    /**
     * Sets all input elements inside container enabled/disabled
     *
     * @param {Element} container
     * @param {boolean} enabled
     */
    const setFormElementsEnabled = function(container, enabled)
    {
        let inputElements = [];
        const inputs = container.getElementsByTagName('INPUT');
        const selects = container.getElementsByTagName('SELECT');
        const textareas = container.getElementsByTagName('TEXTAREA');

        inputElements = Array.prototype.concat.apply(inputElements, inputs);
        inputElements = Array.prototype.concat.apply(inputElements, selects);
        inputElements = Array.prototype.concat.apply(inputElements, textareas);

        for (const inputElement of inputElements) {

            if (inputElement.closest('.tzunghaor_setting_override')) {
                continue;
            }

            inputElement.disabled = !enabled;
        }

    };

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
        const scopeSettingElement = groupElement.querySelector('.tzunghaor_current_scope');
        const parentSettingElement = groupElement.querySelector('.tzunghaor_parent_scope');

        if (enableInput) {
            groupElement.classList.remove('disabled');
            scopeSettingElement.style.display = null;
            parentSettingElement.style.display = 'none';
        } else {
            groupElement.classList.add('disabled');
            scopeSettingElement.style.display = 'none';
            parentSettingElement.style.display = null;
        }

        setFormElementsEnabled(scopeSettingElement, enableInput);
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
        // disable parent setting inputs to save bandwidth - their values would be ignored in any case
        setFormElementsEnabled(valueElement.querySelector('.tzunghaor_parent_scope'), false);
    }

    // javascript for CollectionType
    /**
     * Adds a remove button based on template to the element
     * @param element
     */
    const addRemoveButton = function(element) {
        const removeButton = element.closest('form')
            .querySelector('template.tzunghaor_setting_remove_button')
            .content.firstElementChild.cloneNode(true);
        removeButton.addEventListener('click', function (event) {
            event.preventDefault();
            onValueChanged(event);
            element.remove();
        });
        element.appendChild(removeButton);
    };

    // Iterate through all collection type settings
    for (const collectionElement of document.querySelectorAll('.tzunghaor_current_scope [data-prototype]')) {
        // index will be used to generate unique names when user clicks "add" button
        let index = 0;
        for (const collectionRow of collectionElement.querySelectorAll('.tzunghaor_settings_collection_row')) {
            addRemoveButton(collectionRow);
            index ++;
        }

        // create "add" button based on template
        const addButton = collectionElement.closest('form')
            .querySelector('template.tzunghaor_setting_add_button')
            .content.firstElementChild.cloneNode(true);

        addButton.addEventListener('click', function(event) {
            event.preventDefault();
            onValueChanged(event);
            const div = document.createElement('div');
            const template = collectionElement.dataset.prototype;
            div.innerHTML = template.replace(/__name__/g, collectionElement.dataset.index);
            collectionElement.dataset.index ++;

            const newRow = div.firstChild;
            collectionElement.insertBefore(newRow, addButton);
            addRemoveButton(newRow);

        });
        collectionElement.appendChild(addButton);
        collectionElement.dataset.index = index;
    }

    /**
     * Sends POST request. Request is expected to be an JSON encoded object
     *
     * @param {string} url
     * @param {object} data - will be sent in POST body JSON encoded
     * @param {function(object)} onload - receives the response body
     *
     * @returns {XMLHttpRequest}
     */
    const postRequest = function (url, data, onload) {
        const xhttp = new XMLHttpRequest();
        xhttp.onload = function() {
            if (xhttp.status === 200) {
                onload(xhttp.response);
            }
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
        const scopeListContainer = containerElement.querySelector('.tzunghaor_settings_scopes_list');

        /**
         * Scope search POST response handler
         * @param scopeHierarchy
         */
        const replaceScopeList = function(scopeHierarchy) {
            scopeListContainer.innerHTML = scopeHierarchy;
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
                currentScope: scopeSearchConfig.currentScope,
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
