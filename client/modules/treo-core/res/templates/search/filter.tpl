<div class="form-group">
    <a href="javascript:" class="remove-filter pull-right" data-name="{{name}}">{{#unless notRemovable}}<i class="fas fa-times"></i>{{/unless}}</a>
    <label class="control-label small" data-name="{{name}}">{{translate generalName category='fields' scope=scope}}</label>
    <div class="field" data-name="{{generalName}}">{{{field}}}</div>
</div>