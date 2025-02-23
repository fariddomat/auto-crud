<?php

namespace Fariddomat\AutoCrud\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class RouteGenerator
{
    /**
     * Create routes for the given resource.
     *
     * @param string $modelName The name of the model for which the routes will be generated.
     * @param string $controller The controller that should be used for the resource.
     * @param string $type Either 'api' or 'web' depending on the route type.
     * @param bool $isDashboard Whether the route should be prefixed with 'dashboard'.
     * @return void
     */
    public function create(string $modelName, string $controller, string $type, bool $isDashboard = false): void
    {
        // Convert model name to plural snake_case (e.g., 'Post' -> 'posts')
        $modelName = Str::snake(Str::plural($modelName));

        // Check if we're generating API routes or Web routes
        $isApi = $type === 'api';

        // Determine the appropriate routes file (api.php or web.php)
        $routesPath = base_path($isApi ? 'routes/api.php' : 'routes/web.php');

        // Prepare route code based on the dashboard and type
        $routeCode = $isApi
            ? "Route::apiResource('/{$modelName}', {$controller}::class);"
            : "Route::resource('/{$modelName}', {$controller}::class);";

        if ($isDashboard) {
            $routeCode = "Route::prefix('dashboard')"
                . "->name('dashboard.')"
                . "->group(function() {"
                . "Route::resource('/{$modelName}', {$controller}::class);"
                . "});";
        }

        // Check if the routes file exists, if not, create it
        if (!File::exists($routesPath)) {
            file_put_contents($routesPath, "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n");
        }

        // Read the existing contents of the routes file
        $content = file_get_contents($routesPath);

        // Only append the route if it doesn't already exist in the file
        if (strpos($content, $routeCode) === false) {
            file_put_contents($routesPath, "\n" . $routeCode . "\n", FILE_APPEND);
        }
    }
}
