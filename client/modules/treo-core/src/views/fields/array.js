/*
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoCore" word.
 */

Espo.define('treo-core:views/fields/array', 'class-replace!treo-core:views/fields/array',
    Dep => Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this, 'inline-edit-off', () => {
                this.selected = Espo.Utils.clone(this.model.get(this.name)) || [];
            });
        },

        getItemHtml: function (value) {
            if (this.translatedOptions != null) {
                for (var item in this.translatedOptions) {
                    if (this.translatedOptions[item] == value) {
                        value = item;
                        break;
                    }
                }
            }

            value = value.toString();

            var valueSanitized = this.getHelper().stripTags(value);
            var valueSanitized = valueSanitized.replace(/"/g, '&quot;');
            valueSanitized = valueSanitized.replace(/\\/g, '&bsol;');

            var label = valueSanitized;
            if (this.translatedOptions) {
                label = ((value in this.translatedOptions) ? this.translatedOptions[value] : label);
                label = label.toString();
                label = this.getHelper().stripTags(label);
                label = label.replace(/"/g, '&quot;');
                label = label.replace(/\\/g, '&bsol;');
            }

            var html = '<div class="list-group-item" data-value="' + valueSanitized + '" style="cursor: default;">' + label +
                '&nbsp;<a href="javascript:" class="pull-right" data-value="' + valueSanitized + '" data-action="removeValue"><span class="fas fa-times"></a>' +
                '</div>';

            return html;
        },

        removeValue: function (value) {
            var valueSanitized = this.getHelper().stripTags(value).replace(/\\/g, '\\\\');
            valueSanitized = valueSanitized.replace(/"/g, '\\"');

            this.$list.children('[data-value="' + valueSanitized + '"]').remove();
            var index = this.selected.indexOf(value);
            this.selected.splice(index, 1);
            this.trigger('change');
        },


    })
);