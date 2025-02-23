<?php

namespace Fariddomat\AutoCrud\Services;

use Illuminate\Support\Str;

class CrudGenerator
{
    protected $name;
    protected $fields;

    public function __construct($name, $fields)
    {
        $this->name = Str::studly($name);
        $this->fields = $fields;
    }

    public function parseFields()
    {
        $parsed = [];
        foreach ($this->fields as $field) {
            $parts = explode(':', $field);
            $name = $parts[0];
            $type = $parts[1] ?? 'string';
            $parsed[] = ['name' => $name, 'type' => $type];
        }
        return $parsed;
    }

    public function getTableName()
    {
        return Str::snake(Str::plural($this->name));
    }
}
