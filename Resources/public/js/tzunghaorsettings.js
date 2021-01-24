document.addEventListener('DOMContentLoaded', () => {
    const overrideInputs = document.querySelectorAll('.tzunghaor_setting_override input[type="radio"]');
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

    const onOverrideInputChange = function (event) {
        applyOverride(event.target);
        event.target.closest('.tzunghaor_setting_override').classList.add('tzunghaor_setting_changed');
    };

    for (const input of overrideInputs) {
        applyOverride(input);
        input.addEventListener('change', onOverrideInputChange);
    }

    const onValueChanged = function (event) {
        event.target.closest('.tzunghaor_setting_value').classList.add('tzunghaor_setting_changed');
    };

    for (const valueElement of document.querySelectorAll('.tzunghaor_setting_value')) {
        valueElement.addEventListener('change', onValueChanged);
    }
});
