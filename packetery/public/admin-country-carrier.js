(function ($) {

    $(function () {
        // are we on the right page?
        if ($('.packetery-carrier-options-page').length === 0) {
            return;
        }

        var Multiplier = function (wrapperSelector) {
            var $wrapper = $(wrapperSelector),
                multiplier = this;

            this.registerListeners = function () {
                $wrapper
                    .on('click', '[data-packetery-replication-add]', function () {
                        multiplier.addItem(this);
                    })
                    .on('click', '[data-packetery-replication-delete]', function () {
                        multiplier.deleteItem(this);
                    })
                    .each(function () {
                        multiplier.toggleDeleteButton($(this).find('[data-packetery-replication-item-container]'));
                    });
            };

            this.addItem = function ( button ) {
                var $container = $(button).closest(wrapperSelector).find('[data-packetery-replication-item-container]'),
                    $template = getTemplateClone($container);

                updateIds($template, newId++);
                $container.append($template);
                $('input', $template).eq(0).focus();
                this.toggleDeleteButton($container);
            };

            this.deleteItem = function ( button ) {
                var $row = $(button).closest('[data-packetery-replication-item]'),
                    $container = $row.closest('[data-packetery-replication-item-container]');

                $row.remove();
                this.toggleDeleteButton($container);
            };

            this.toggleDeleteButton = function ($container) {
                var optionsCount = $container.find('[data-packetery-replication-item]').length,
                    $buttons = $container.find('[data-packetery-replication-delete]'),
                    minItems = parseInt($container.data('packetery-replication-min-items'));

                ( optionsCount > minItems ? $buttons.show() : $buttons.hide() );
            };

            /**
             * Find the highest counter in the rendered form (invalid form gets re-rendered with its submitted new_* form items)
             */
            function findMaxNewId() {
                var $newInputs = $(wrapperSelector + ' [name*=' + prefix + ']'),
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

            function getTemplateClone(container) {
                var formId = container.closest('form').attr('id');
                var formTemplateId = formId + '_template';
                return $('#' + formTemplateId).find(wrapperSelector).find('[data-packetery-replication-item]').first().clone(); // table tr is currently the replication item
            }

            /**
             * Update references to element names to make them unique; the value itself doesn't matter: [0] -> [new_234]
             */
            function updateIds($html, id) {
                $('input, select, label, .packetery-input-validation-message', $html).each(function (i, element) {
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
                var regExp = new RegExp('\\' + delimiters[0] + '0\\' + delimiters[1]);
                $element.attr(attrName, value.replace(regExp, delimiters[0] + prefix + id + delimiters[1]));
            }

            this.registerListeners();
        };

        new Multiplier('.js-weight-rules');
        new Multiplier('.js-surcharge-rules');
    });

})(jQuery);
