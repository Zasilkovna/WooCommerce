(function ($) {

    $(function () {
        // are we on the right page?
        if ($('.packetery-carrier-options-page').length === 0) {
            return;
        }

        var Multiplier = function () {
            this.registerListeners = function (wrapperSelector) {
                var $wrappers = $(wrapperSelector);
                $wrappers
                    .on('click', '.js-add', function () {
                        multiplier.addOption(this, $wrappers); // todo use wrapperSelector instead
                    })
                    .on('click', '.js-delete', function () {
                        multiplier.deleteOption(this);
                    })
                    .each(function () {
                        multiplier.toggleDeleteButton($(this));
                    });
            };

            this.addOption = function (button, $wrappers) {
                var wrapperClassName = $wrappers.first().attr('class'),
                    $wrapper = $(button).closest('.' + wrapperClassName),
                    // $templateElement = $($wrapper.data('packetery-template')),
                    $template = getTemplateClone($wrapper);

                updateIds($template, newId++);
                $wrapper.find('table').append($template);
                $('input', $template).eq(0).focus();
                this.toggleDeleteButton($wrapper);
            };

            this.deleteOption = function (button) {
                var $row = $(button).closest('tr'),
                    $table = $row.closest('table');

                $row.remove();
                this.toggleDeleteButton($table);
            };

            this.toggleDeleteButton = function ($wrapper) {
                var optionsCount = $wrapper.find('tr').length,
                    $buttons = $wrapper.find('button.js-delete');

                (optionsCount > 1) ? $buttons.show() : $buttons.hide();
            };

            /**
             * Find the highest counter in the rendered form (invalid form gets re-rendered with its submitted new_* form items)
             */
            function findMaxNewId() {
                var $newInputs = $('[name*=' + prefix + ']'),
                    maxNewId = 1;

                $newInputs.each(function () {
                    var newIdMatch = $(this).attr('name').match('\\[' + prefix + '(\\d+)\\]');
                    var counter = parseInt(newIdMatch[1]);
                    maxNewId = Math.max(maxNewId, counter + 1);
                });

                return maxNewId;
            }

            var prefix = 'new_',
                newId = findMaxNewId();

            function getTemplateClone($wrapper) {
                var $template = $wrapper.find('tr').first().clone();
                $template.find('input').val(''); // todo will not be needed
                return $template;
            }

            /**
             * Update references to element names to make them unique; the value itself doesn't matter: [0] -> [new_234]
             */
            function updateIds($html, id) {

                // todo rename container
                // todo make sure weight rules work

                $('input, select, label, span', $html).each(function (i, element) {
                    var $element = $(element);

                    updateId($element, 'name', id, ['[', ']']);
                    updateId($element, 'data-lfv-message-id', id, ['-', '-']);
                    updateId($element, 'for', id, ['-', '-']);
                    updateId($element, 'id', id, ['-', '-']);
                });
            }

            function updateId($element, attrName, id, delimiters) {
                var value = $element.attr(attrName);
                if (!value) {
                    return;
                }

                // don't use data() because we want the raw values, not parsed json arrays/objects
                var regExp = new RegExp('\\' + delimiters[0] + '(new_)?\\d+\\' + delimiters[1]); // todo replace  delimiters[0] + '0\\' + delimiters[1]
                $element.attr(attrName, value.replace(regExp, delimiters[0] + prefix + id + delimiters[1]));
            }

        };

        var multiplier = new Multiplier();

        multiplier.registerListeners('.js-weight-rules');
        multiplier.registerListeners('.js-surcharge-rules');
    });

})(jQuery);
