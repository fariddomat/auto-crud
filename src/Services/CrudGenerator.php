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

    /**
     * Parse the fields argument and generate the validation rules.
     *
     * @return string
     */
    public function generateRules()
    {
        $rules = [];

        foreach ($this->fields as $field) {
            $parts = explode(':', $field);
            $name = $parts[0];
            $type = $parts[1] ?? 'string';

            // Generate validation rules based on field types
            switch ($type) {
                case 'string':
                    $rules[] = "'$name' => 'required|string|max:255',";
                    break;

                case 'decimal':
                case 'integer':
                    $rules[] = "'$name' => 'required|numeric',";
                    break;

                case 'text':
                    $rules[] = "'$name' => 'nullable|string',";
                    break;

                case 'select':
                    $tableName = Str::snake(Str::plural(Str::beforeLast($name, '_id')));
                    $rules[] = "'$name' => 'required|exists:$tableName,id',";
                    break;

                case 'boolean':
                    $rules[] = "'$name' => 'sometimes|boolean',";
                    break;

                case 'file':
                    $rules[] = "'$name' => 'sometimes|file|max:2048',"; // Max size: 2MB
                    break;

                case 'image':
                    $rules[] = "'$name' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',"; // Image validation
                    break;

                case 'images':
                    $rules[] = "'$name' => 'sometimes|array',";
                    $rules[] = "'$name.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',"; // Validate each image
                    break;

                default:
                    $rules[] = "'$name' => 'required|string',"; // Default fallback
                    break;
            }
        }

        return implode("\n", $rules);
    }

    /**
     * Generate the model file along with the rules method.
     */
    public function generateModel()
    {
        $modelPath = app_path("Models/{$this->name}.php");

        if (!file_exists($modelPath)) {
            $modelContent = "<?php\n\nnamespace App\Models;\n\nuse Illuminate\Database\Eloquent\Model;\n\nclass {$this->name} extends Model\n{\n";

            // Generate fillable property using parsed fields
            $fillableFields = array_map(fn($field) => "'{$field['name']}'", $this->parseFields());
            $modelContent .= "    protected \$fillable = [" . implode(", ", $fillableFields) . "];\n\n";

            // Add the generated rules method
            $modelContent .= "    public static function rules()\n    {\n";
            $modelContent .= "        return [\n" . $this->generateRules() . "\n    ];\n";
            $modelContent .= "    }\n}\n";

            // Write to the model file
            file_put_contents($modelPath, $modelContent);
        }
    }


    public function parseFields()
    {
        $parsed = [];
        foreach ($this->fields as $field) {
            $parts = explode(':', $field);
            $name = $parts[0];
            $type = $parts[1] ?? 'string';
            if ($type == 'select') {
                $type = "unsignedBigInteger";
            }
            if ($type == 'file' || $type=="image"  || $type=="images") {
                $type = "string";
            }
            $parsed[] = ['name' => $name, 'type' => $type];
        }
        return $parsed;
    }

    public function viewParseFields()
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
