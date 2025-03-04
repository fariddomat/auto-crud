<?php

namespace Fariddomat\AutoCrud\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationGenerator
{
    /**
     * Generate a migration file for the given table.
     *
     * @param string $name Model name (used for console output)
     * @param string $tableName Name of the table
     * @param array $columns Array of columns with name, type, and modifiers
     * @param object|null $command Optional console command instance for feedback
     * @param bool $withSoftDeletes Include soft deletes if true
     * @return bool Success status
     */
    public static function generate($name, $tableName, $columns, $command = null, $withSoftDeletes = false)
    {
        $migrationFileName = database_path('migrations/' . date('Y_m_d_His') . "_create_{$tableName}_table.php");
        $migrationContent = self::getTemplate($tableName, $columns, $withSoftDeletes);

        try {
            File::ensureDirectoryExists(dirname($migrationFileName));
            File::put($migrationFileName, $migrationContent);
            static::info($command, "Migration created: {$migrationFileName}");
            return true;
        } catch (\Exception $e) {
            static::error($command, "Failed to create migration file: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate the migration template with fields and optional soft deletes.
     *
     * @param string $tableName Name of the table
     * @param array $columns Array of columns with name, type, and modifiers
     * @param bool $withSoftDeletes Include soft deletes if true
     * @return string Migration content
     */
    private static function getTemplate($tableName, $columns, $withSoftDeletes)
    {
        $fields = "";
        foreach ($columns as $column) {
            $fieldDefinition = "\$table->{$column['type']}('{$column['name']}')";

            // Apply modifiers
            if (in_array('nullable', $column['modifiers'] ?? [])) {
                $fieldDefinition .= "->nullable()";
            }
            if (in_array('unique', $column['modifiers'] ?? [])) {
                $fieldDefinition .= "->unique()";
            }
            if ($column['type'] === 'unsignedBigInteger' && Str::endsWith($column['name'], '_id')) {
                $relatedTable = Str::snake(Str::plural(Str::beforeLast($column['name'], '_id')));
                $fieldDefinition .= "->foreign()->references('id')->on('$relatedTable')->onDelete('cascade')";
            }

            $fields .= "\n            " . $fieldDefinition . ";";
        }

        // Include soft deletes if requested
        $softDeletes = $withSoftDeletes ? "\n            \$table->softDeletes();" : "";

        return "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('$tableName', function (Blueprint \$table) {
            \$table->id();$fields$softDeletes
            \$table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('$tableName');
    }
};";
    }

    /**
     * Helper method to output info messages to the console.
     *
     * @param object|null $command Command instance
     * @param string $message Message to display
     */
    protected static function info($command, $message)
    {
        if ($command) {
            $command->info("\033[32m $message \033[0m");
        } else {
            echo "\033[32m $message \033[0m\n";
        }
    }

    /**
     * Helper method to output error messages to the console.
     *
     * @param object|null $command Command instance
     * @param string $message Message to display
     */
    protected static function error($command, $message)
    {
        if ($command) {
            $command->error($message);
        } else {
            echo "\033[31m $message \033[0m\n";
        }
    }
}
