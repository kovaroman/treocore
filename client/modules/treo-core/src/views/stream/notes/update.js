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

Espo.define('treo-core:views/stream/notes/update', 'views/stream/notes/update', function (Dep) {

    return Dep.extend({

        template: 'treo-core:stream/notes/update',

        customLabels: {},

        setup: function () {
            var data = this.model.get('data');

            var fields = data.fields || [];

            this.createMessage();

            this.wait(true);
            this.getModelFactory().create(this.model.get('parentType'), function (model) {
                var modelWas = model;
                var modelBecame = model.clone();

                data.attributes = data.attributes || {};

                modelWas.set(data.attributes.was);
                modelBecame.set(data.attributes.became);

                this.fieldsArr = [];

                fields = this.addMultilangFields(model, fields);

                fields.forEach(function (field) {
                    let type = this.model.get('attributeType') || model.getFieldType(field) || 'base';
                    if (model.getFieldParam(field, 'isMultilang') && this.getConfig().get('isMultilangActive')) {
                        type = this.getMetadata().get(['fields', type, 'defaultFieldType']);
                    }
                    let viewName = model.getFieldParam(field, 'view') || this.getFieldManager().getViewName(type);
                    this.createView(field + 'Was', viewName, {
                        el: this.options.el + '.was',
                        model: modelWas,
                        readOnly: true,
                        defs: {
                            name: field
                        },
                        mode: 'detail',
                        inlineEditDisabled: true
                    });
                    this.createView(field + 'Became', viewName, {
                        el: this.options.el + '.became',
                        model: modelBecame,
                        readOnly: true,
                        defs: {
                            name: field
                        },
                        mode: 'detail',
                        inlineEditDisabled: true
                    });

                    this.fieldsArr.push({
                        field: field,
                        was: field + 'Was',
                        became: field + 'Became',
                        customLabel: this.customLabels[field] ? this.customLabels[field] : false
                    });

                }, this);

                this.wait(false);

            }, this);
        },

        addMultilangFields(model, fields) {
            let additionalFields = [];
            let inputLanguageList = this.getConfig().get('inputLanguageList') || [];
            (fields || []).forEach(field => {
                if (model.getFieldParam(field, 'isMultilang')) {
                    let langFieldNameList = inputLanguageList.map(lang => this.getInputLangName(lang, field)) || [];
                    langFieldNameList.forEach(langField => {
                        if (langField in model.attributes) {
                            this.customLabels[langField] = this.getCustomLabel(field, langField);
                            additionalFields.push(langField);
                        }
                    });
                }
            });
            return fields.concat(additionalFields);
        },

        getInputLangName(lang, field) {
            return lang.split('_').reduce((prev, curr) => prev + Espo.utils.upperCaseFirst(curr.toLowerCase()), field);
        },

        getCustomLabel(field, langField) {
            let label = '';
            label += this.translate(field, 'fields', this.model.get('parentType')) + " › ";
            label += langField.slice(-4, -2).toLowerCase() + "_" + langField.slice(-2).toUpperCase();
            return label;
        }
    });
});

