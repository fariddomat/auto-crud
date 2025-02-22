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
        $fields = $this->argument('fields');

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
            $this->generateBladeViews($name, $isDashboard);
        }

        $this->info("\033[34m CRUD for $name has been created successfully! \033[0m");
    }

    private function generateController($name, $isApi, $isDashboard)
    {
        $namespace = $isDashboard ? 'App\Http\Controllers\Dashboard' : 'App\Http\Controllers';
        $controllerPath = app_path($isDashboard ? "Http/Controllers/Dashboard/{$name}Controller.php" : "Http/Controllers/{$name}Controller.php");

        $routePrefix = $isDashboard ? 'dashboard.'.Str::plural(Str::snake($name)): Str::plural(Str::snake($name)); // Pluralize route prefix

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

    private function generateBladeViews($name, $isDashboard)
    {
        $folderName = $isDashboard ? 'dashboard.'.Str::plural(Str::snake($name)) : Str::plural(Str::snake($name)); // Use plural for view folder name
        $basePath = resource_path("views/{$folderName}");
    
        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true, true);
        }
    
        // Modify the index view to use the data-table component
        $indexView = <<<EOT
    <x-app-layout>
        <div class="container">
            <h1>قائمة {$name}</h1>
            <a href="{{ route('{$folderName}.create') }}" class="btn btn-primary">إضافة جديد</a>
    
            {{-- Data Table Component --}}
            <x-autocrud::data-table
                :columns="['id', 'name']"
                :data="\$records"
                routePrefix="{$folderName}"
                :show="true"
                :edit="true"
                :delete="true"
            />
        </div>
    </x-app-layout>
EOT;
    
        File::put("$basePath/index.blade.php", $indexView);
        $this->info("\033[32m Blade view created: {$basePath}/index.blade.php \033[0m");
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
