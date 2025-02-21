<?php
namespace Fariddomat\AutoCrud\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeAutoCrud extends Command
{
    protected $signature = 'make:auto-crud {name} {fields?*} {--api}';
    protected $description = 'Generate a complete CRUD module with optional API support.';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $tableName = Str::snake($name);
        $isApi = $this->option('api');
        $fields = $this->argument('fields');

        $this->info("\033[34m Generating Auto CRUD for $name... \033[0m");

        // تحليل الحقول المدخلة
        $columns = [];
        foreach ($fields as $field) {
            [$columnName, $columnType] = explode(':', $field);
            $columns[$columnName] = $columnType;
        }

        // إنشاء Model بدون -m لأننا سنولد migration يدويًا
        $this->call('make:model', ['name' => $name]);

        // إنشاء Controller
        $controllerOptions = $isApi ? ['--api' => true] : [];
        $this->call('make:controller', ['name' => "{$name}Controller"] + $controllerOptions);

        // إنشاء Livewire Components
        $this->call('make:livewire', ['name' => "admin.{$name}-index"]);
        $this->call('make:livewire', ['name' => "admin.{$name}-form"]);

        // إنشاء ملف Migration ديناميكي
        $this->generateMigration($name, $tableName, $columns);

        $this->info("\033[34m CRUD for $name has been created successfully! \033[0m");
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
