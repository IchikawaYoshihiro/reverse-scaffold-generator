<?php
namespace Ichikawayac\ReverseScaffoldGenerator\Generators;

class ViewBaseGenerator extends BaseGenerator
{
    protected $view_name;

    public function generate()
    {
        $replaces = [
            'DummyValiables' => $this->valiables_name,
            'DummyValiable'  => $this->valiable_name,
            'DummyTableHead' => $this->buildTableHead(),
            'DummyTableBody' => $this->buildTableBody(),
            'DummyList'      => $this->buildDefinitionList(),
            'DummyInputArea' => $this->buildForm(),
            'DummyViewName'  => $this->getViewFullName('.'),
            'DummyRouteName' => $this->getRouteFullName('.'),
            'DummyLangName'  => $this->getLangFullName(),
        ];

        $stub = static::getStubFile('views/'.$this->view_name.'.blade.stub');

        static::fileGenerate($stub, $replaces, $this->getGenerateFilePath());
    }


    public function getGenerateFilePath()
    {
        return $this->getViewFilePath($this->view_name);
    }


    protected function buildTableHead()
    {
        return $this->getFillableFields()->map(function($column) {
            return "<th>{{ {$this->__($this->valiable_name, $column->Field)} }}</th>";
        })->implode("\n                ");
    }


    protected function buildTableBody()
    {
        return $this->getFillableFields()->map(function($column) {
            // hide password
            if(static::has($column->Field, 'password')) {
                return "<td>******</td>";
            }
            if(static::isBoolean($column)) {
                return "<td>{{ \${$this->valiable_name}->{$column->Field} ? {$this->__('crud', 'checked')} : {$this->__('crud', 'not_checked')} }}</td>";
            }

            return "<td>{{ \${$this->valiable_name}->{$column->Field} }}</td>";
        })->implode("\n                ");
    }


    protected function buildDefinitionList()
    {
        return $this->getFillableFields()->map(function($column) {
            $dt = "        <dt>{{ {$this->__($this->valiable_name, $column->Field)} }}</dt>\n";
            // hide password
            if(static::has($column->Field, 'password')) {
                return $dt."        <dd>******</dd>";
            }
            if(static::isBoolean($column)) {
                return $dt."        <dd>{{ \${$this->valiable_name}->{$column->Field} ? {$this->__('crud', 'checked')} : {$this->__('crud', 'not_checked')} }}</dd>";
            }

            return $dt."        <dd>{{ \${$this->valiable_name}->{$column->Field} }}</dd>";
        })->implode("\n");
    }


    protected function buildForm()
    {
        return $this->getFillableFields()->map(function($item) {
            $input = $this->buildInput($item);
            $error = $this->buildError($item);
            return <<< EOM
<div class="form-group">
{$input}
{$error}
</div>
EOM;
        })->implode("\n\n");
    }


    protected function buildError($item)
    {
        return <<<EOM
    @if (\$errors->has('{$item->Field}'))
        <div class="alert alert-danger">
            @foreach (\$errors->get('{$item->Field}') as \$error)
                <div>{{ \$error }}</div>
            @endforeach
        </div>
    @endif
EOM;
}


    protected function buildRequired($item)
    {
        return $item->Null === 'NO'
            ? "<span class=\"badge badge-danger\">{{ {$this->__('crud', 'required')} }}</span>"
            : "<span class=\"badge badge-info\">{{ {$this->__('crud', 'optional')} }}</span>";
    }


    protected function buildInput($item)
    {
        $requiured = $this->buildRequired($item);

        // textarea
        if (static::has($item->Type, 'text')) {
            return <<< EOM
    <label for="{$item->Field}">{$requiured} {{ {$this->__($this->valiable_name, $item->Field)} }}</label>
    <textarea name="{$item->Field}" id="{$item->Field}" class="form-control">{{ old('{$item->Field}', \${$this->valiable_name}->{$item->Field} ?? '') }}</textarea>
EOM;
        }

        // checkbox
        if (static::isBoolean($item)) {
            return <<< EOM
    {$requiured}
    <div class="custom-control custom-checkbox">
        <input type="checkbox" name="{$item->Field}" id="{$item->Field}" class="custom-control-input" value="1" {{ old('{$item->Field}', \${$this->valiable_name}->{$item->Field} ?? false) ? 'checked' : ''}}>
        <label for="{$item->Field}" class="custom-control-label">{{ {$this->__($this->valiable_name, $item->Field)} }}</label>
    </div>
EOM;
        }

        // input type=text|password|email|number|date|time|datetime-local
        return <<< EOM
    <label for="{$item->Field}">{$requiured} {{ {$this->__($this->valiable_name, $item->Field)} }}</label>
    <input type="{$this->judgeType($item)}" name="{$item->Field}" id="{$item->Field}" class="form-control" value="{$this->judgeValue($item)}">
EOM;
    }


    protected function judgeType($item)
    {
        // special
        if (static::has($item->Field, 'email')) {
            return 'email';
        }
        if (static::has($item->Field, 'password')) {
            return 'password';
        }

        // types
        if (static::has($item->Type, 'int')) {
            return 'number';
        }
        if (static::is($item->Type, 'time')) {
            return 'time';
        }
        if (static::is($item->Type, 'date')) {
            return 'date';
        }
        return 'text';
    }


    protected function judgeValue($item)
    {
        // special
        if (static::has($item->Field, 'password')) {
            return '';
        }

        return "{{ old('{$item->Field}', \${$this->valiable_name}->{$item->Field} ?? '') }}";
    }


    protected function judgeAttr($item)
    {
        if (static::isBoolean($item)) {
            return "{{ old('{$item->Field}', \${$this->valiable_name}->{$item->Field} ?? false) ? 'checked' : ''}}";
        }
    }
}
