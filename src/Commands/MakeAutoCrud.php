<?php

namespace Fariddomat\AutoCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeAutoCrud extends Command
{
    protected $signature = 'make:auto-crud {name} {fields?*} {--api} {--dashboard}';

    protected $description = 'Generate a complete CRUD module with optional API support.';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $tableName = Str::snake(Str::plural($name)); // Pluralize the table name
        $isApi = $this->option('api');
        $isDashboard = $this->option('dashboard');
        $fields = $this->argument('fields') ?: $this->getModelFields($name);


        $parsedFields = $this->parseFields($fields);
        // $this->generateBladeViews($name, $isDashboard, $parsedFields);

        $this->info("\033[34m Generating Auto CRUD for $name... \033[0m");

        // Analyze input fields
        $columns = [];
        foreach ($fields as $field) {
            [$columnName, $columnType] = explode(':', $field);
            $columns[$columnName] = $columnType;
        }

        // Create Model
        $this->call('make:model', ['name' => $name]);

        // Create Controller
        $this->generateController($name, $isApi, $isDashboard);

        // Create Livewire Components only if not API
        if (!$isApi) {
            $prefix = $isDashboard ? 'dashboard.' : 'frontend.';
            $this->call('make:livewire', ['name' => "{$prefix}{$name}-index"]);
            $this->call('make:livewire', ['name' => "{$prefix}{$name}-form"]);
        }

        // Create Migration
        $this->generateMigration($name, $tableName, $columns);

        // Create Views if Web
        if (!$isApi) {
            $this->generateBladeViews($name, $isDashboard, $parsedFields);
        }

        $this->info("\033[34m CRUD for $name has been created successfully! \033[0m");
    }

    private function getModelFields($modelName)
{
    $modelClass = "App\\Models\\$modelName";

    if (!class_exists($modelClass)) {
        return [];
    }

    $model = new $modelClass;
    $table = $model->getTable();

    if (!\Schema::hasTable($table)) {
        return [];
    }

    $columns = \Schema::getColumnListing($table);

    // استثناء الحقول الافتراضية مثل `id`, `created_at`, `updated_at`
    $excludedColumns = ['id', 'created_at', 'updated_at', 'deleted_at'];
    return array_diff($columns, $excludedColumns);
}

    private function parseFields($fields)
{
    $parsed = [];

    foreach ($fields as $field) {
        $parts = explode(':', $field);
        $name = $parts[0];
        $type = $parts[1] ?? 'string'; // Default to text input

        $parsed[] = ['name' => $name, 'type' => $type];
    }

    return $parsed;
}


    private function generateController($name, $isApi, $isDashboard)
    {
        $namespace = $isDashboard ? 'App\Http\Controllers\Dashboard' : 'App\Http\Controllers';
        $controllerPath = app_path($isDashboard ? "Http/Controllers/Dashboard/{$name}Controller.php" : "Http/Controllers/{$name}Controller.php");

        $routePrefix = $isDashboard ? 'dashboard.' . Str::plural(Str::snake($name)) : Str::plural(Str::snake($name)); // Pluralize route prefix

        $controllerContent = <<<EOT
<?php

namespace $namespace;

use App\Models\\$name;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class {$name}Controller extends Controller
{
EOT;

        if ($isApi) {
            // API Methods
            $controllerContent .= <<<EOT

    public function index()
    {
        return response()->json($name::all());
    }

    public function store(Request \$request)
    {
        \$validated = \$request->validate($name::rules());

        \$record = $name::create(\$validated);

        return response()->json(\$record, 201);
    }

    public function show($name \$record)
    {
        return response()->json(\$record);
    }

    public function update(Request \$request, $name \$record)
    {
        \$validated = \$request->validate($name::rules());

        \$record->update(\$validated);

        return response()->json(\$record);
    }

    public function destroy($name \$record)
    {
        \$record->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
EOT;
        } else {
            // Web Methods with Blade
            $controllerContent .= <<<EOT

    public function index()
    {
        \$records = $name::all();
        return view('{$routePrefix}.index', compact('records'));
    }

    public function create()
    {
        return view('{$routePrefix}.create');
    }

    public function store(Request \$request)
    {
        \$validated = \$request->validate($name::rules());
        $name::create(\$validated);
        return redirect()->route('{$routePrefix}.index')->with('success', 'تم الإضافة بنجاح');
    }

    public function edit($name \$record)
    {
        return view('{$routePrefix}.edit', compact('record'));
    }

    public function update(Request \$request, $name \$record)
    {
        \$validated = \$request->validate($name::rules());
        \$record->update(\$validated);
        return redirect()->route('{$routePrefix}.index')->with('success', 'تم التحديث بنجاح');
    }

    public function destroy($name \$record)
    {
        \$record->delete();
        return redirect()->route('{$routePrefix}.index')->with('success', 'تم الحذف بنجاح');
    }
EOT;
        }

        $controllerContent .= "\n}\n";

        File::put($controllerPath, $controllerContent);
        $this->info("\033[32m Controller created: {$controllerPath} \033[0m");
    }

    private function generateBladeViews($name, $isDashboard, $fields)
    {
        $folderName = $isDashboard ? 'dashboard.' . Str::plural(Str::snake($name)) : Str::plural(Str::snake($name)); // Use plural for view folder name
        $basePath = resource_path("views/" . ($isDashboard ? "dashboard/" . Str::plural(Str::snake($name)) : Str::plural(Str::snake($name))));

        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true, true);
        }

        File::put("$basePath/create.blade.php", $this->generateCreateBlade($folderName, $fields));
        File::put("$basePath/edit.blade.php", $this->generateEditBlade($folderName, $fields));

        $this->info("\033[32m Views created in: {$basePath} \033[0m");
        // Modify the index view to use the data-table component
        $indexView = <<<EOT
    <x-app-layout>
    <div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">$name</h1>
    <a href="{{ route('{$folderName}.create') }}" class="px-4 py-2 bg-blue-500 text-white rounded shadow"  wire:navigate>➕ @lang('site.add') $name</a>

    <div class="overflow-x-auto mt-4">
            {{-- Data Table Component --}}
            <x-autocrud::table
                :columns="['id', 'name']"
                :data="\$records"
                routePrefix="{$folderName}"
                :show="true"
                :edit="true"
                :delete="true"
            />
        </div>
        </div>
    </x-app-layout>
EOT;

        File::put("$basePath/index.blade.php", $indexView);
        $this->info("\033[32m Blade view created: {$basePath}/index.blade.php \033[0m");
    }

    private function generateCreateBlade($folderName, $fields)
    {
        $formFields = $this->generateFormFields($fields);

        return <<<EOT
    <x-app-layout>
        <div class="container mx-auto p-6">
            <h1 class="text-2xl font-bold mb-4">
                @lang('site.create') @lang('site.{$folderName}')
            </h1>

            <form action="{{ route('{$folderName}.store') }}" method="POST" class="bg-white p-6 rounded-lg shadow-md">
                @csrf
                {$formFields}
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded shadow hover:bg-blue-700">
                    @lang('site.create')
                </button>
            </form>
        </div>
    </x-app-layout>
    EOT;
    }

    private function generateEditBlade($folderName, $fields)
    {
        $formFields = $this->generateFormFields($fields, true);

        return <<<EOT
    <x-app-layout>
        <div class="container mx-auto p-6">
            <h1 class="text-2xl font-bold mb-4">
                @lang('site.edit') @lang('site.{$folderName}')
            </h1>

            <form action="{{ route('{$folderName}.update', \$record->id) }}" method="POST" class="bg-white p-6 rounded-lg shadow-md">
                @csrf
                @method('PUT')
                {$formFields}
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded shadow hover:bg-blue-700">
                    @lang('site.update')
                </button>
            </form>
        </div>
    </x-app-layout>
    EOT;
    }
    private function generateFormFields($fields, $isEdit = false)
    {
        $output = "";

        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'];
            $value = $isEdit ? "{{ old('$name', \$record->$name) }}" : "{{ old('$name') }}";

            if (in_array($type, ['text', 'string'])) {
                $output .= <<<EOT
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">@lang('site.{$name}')</label>
                    <input type="text" name="{$name}" value="{$value}" class="w-full border border-gray-300 rounded p-2">
                    @error('{$name}')
                        <span class="text-red-500 text-sm">{{ \$message }}</span>
                    @enderror
                </div>
                EOT;
            } elseif ($type == 'decimal' || $type == 'integer') {
                $step = $type == 'decimal' ? 'step="0.01"' : '';
                $output .= <<<EOT
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">@lang('site.{$name}')</label>
                    <input type="number" name="{$name}" value="{$value}" class="w-full border border-gray-300 rounded p-2" {$step}>
                    @error('{$name}')
                        <span class="text-red-500 text-sm">{{ \$message }}</span>
                    @enderror
                </div>
                EOT;
            } elseif ($type == 'text') {
                $output .= <<<EOT
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">@lang('site.{$name}')</label>
                    <textarea name="{$name}" class="w-full border border-gray-300 rounded p-2">{$value}</textarea>
                    @error('{$name}')
                        <span class="text-red-500 text-sm">{{ \$message }}</span>
                    @enderror
                </div>
                EOT;
            } elseif ($type == 'select') {
                $output .= <<<EOT
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">@lang('site.{$name}')</label>
                    <select name="{$name}" class="w-full border border-gray-300 rounded p-2">
                        <option value="">@lang('site.select_{$name}')</option>
                        @foreach (\$options as \$option)
                            <option value="{{ \$option->id }}" {{ \$record->{$name} == \$option->id ? 'selected' : '' }}>{{ \$option->name }}</option>
                        @endforeach
                    </select>
                    @error('{$name}')
                        <span class="text-red-500 text-sm">{{ \$message }}</span>
                    @enderror
                </div>
                EOT;
            }
        }

        return $output;
    }

    private function generateMigration($name, $tableName, $columns)
    {
        $migrationFileName = database_path('migrations/' . date('Y_m_d_His') . "_create_{$tableName}_table.php");

        $migrationContent = <<<EOT
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('$tableName', function (Blueprint \$table) {
            \$table->id();
EOT;

        foreach ($columns as $column => $type) {
            $migrationContent .= "\n\t\t\t\$table->$type('$column');";
        }

        $migrationContent .= "\n\t\t\t\$table->timestamps();\n\t\t});\n\t}\n\n";

        $migrationContent .= "    public function down() {\n";
        $migrationContent .= "        Schema::dropIfExists('$tableName');\n    }\n};";

        File::put($migrationFileName, $migrationContent);

        $this->info("\033[32m Migration created: {$migrationFileName} \033[0m");
    }
}
