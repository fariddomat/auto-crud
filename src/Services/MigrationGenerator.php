<?php

namespace Fariddomat\AutoCrud\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MigrationGenerator
{
    public static function generate($name, $tableName, $columns)
    {
        $migrationFileName = database_path('migrations/' . date('Y_m_d_His') . "_create_{$tableName}_table.php");
        $migrationContent = self::getTemplate($tableName, $columns);
        File::put($migrationFileName, $migrationContent);
        echo "\033[32m Migration created: {$migrationFileName} \033[0m\n";
    }

    private static function getTemplate($tableName, $columns)
    {
        $fields = "";
        foreach ($columns as $column) {
            $fields .= "\n            \$table->{$column['type']}('{$column['name']}');";
        }

        return "<?php

            use Illuminate\Database\Migrations\Migration;
            use Illuminate\Database\Schema\Blueprint;
            use Illuminate\Support\Facades\Schema;

            return new class extends Migration {
                public function up() {
                    Schema::create('$tableName', function (Blueprint \$table) {
                        \$table->id();
                        $fields
                        \$table->timestamps();
                    });
                }

                public function down() {
                    Schema::dropIfExists('$tableName');
                }
            };";
    }
}
