<?php

namespace Fariddomat\AutoCrud\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ControllerGenerator
{
    public static function generate($name, $isApi, $isDashboard)
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
use App\Helpers\ImageHelper;


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

                    if (\$request->hasFile('image')) {
                        \$validated['image'] = ImageHelper::storeImageInPublicDirectory(\$request->file('image'), 'uploads/{$routePrefix}');
                    }

                    if (\$request->hasFile('file')) {
                        \$validated['file'] = \$request->file('file')->store('uploads/{$routePrefix}', 'public');
                    }

                    if (\$request->hasFile('images')) {
                        \$validated['images'] = [];
                        foreach (\$request->file('images') as \$image) {
                            \$validated['images'][] = ImageHelper::storeImageInPublicDirectory(\$image, 'uploads/{$routePrefix}');
                        }
                        \$validated['images'] = json_encode(\$validated['images']);
                    }

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

                    if (\$request->hasFile('image')) {
                        ImageHelper::removeImageInPublicDirectory(\$record->image);
                        \$validated['image'] = ImageHelper::storeImageInPublicDirectory(\$request->file('image'), 'uploads/{$routePrefix}');
                    }

                    if (\$request->hasFile('file')) {
                        \$validated['file'] = \$request->file('file')->store('uploads/{$routePrefix}', 'public');
                    }

                    if (\$request->hasFile('images')) {
                        if (\$record->images) {
                            foreach (json_decode(\$record->images, true) as \$oldImage) {
                                ImageHelper::removeImageInPublicDirectory(\$oldImage);
                            }
                        }
                        \$validated['images'] = [];
                        foreach (\$request->file('images') as \$image) {
                            \$validated['images'][] = ImageHelper::storeImageInPublicDirectory(\$image, 'uploads/{$routePrefix}');
                        }
                        \$validated['images'] = json_encode(\$validated['images']);
                    }

                    \$record->update(\$validated);
                    return response()->json(\$record);
                }

                public function destroy($name \$record)
                {
                    if (\$record->image) {
                        ImageHelper::removeImageInPublicDirectory(\$record->image);
                    }

                    if (\$record->images) {
                        foreach (json_decode(\$record->images, true) as \$image) {
                            ImageHelper::removeImageInPublicDirectory(\$image);
                        }
                    }

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

                if (\$request->hasFile('image')) {
                    \$validated['image'] = ImageHelper::storeImageInPublicDirectory(\$request->file('image'), 'uploads/{$routePrefix}');
                }

                if (\$request->hasFile('file')) {
                    \$validated['file'] = \$request->file('file')->store('uploads/{$routePrefix}', 'public');
                }

                if (\$request->hasFile('images')) {
                    \$validated['images'] = [];
                    foreach (\$request->file('images') as \$image) {
                        \$validated['images'][] = ImageHelper::storeImageInPublicDirectory(\$image, 'uploads/{$routePrefix}');
                    }
                    \$validated['images'] = json_encode(\$validated['images']);
                }

                $name::create(\$validated);
                return redirect()->route('{$routePrefix}.index')->with('success', 'تم الإضافة بنجاح');
            }

            public function edit(\$record)
            {
            \$record = $name::findOrFail(\$record);
                return view('{$routePrefix}.edit', compact('record'));
            }

            public function update(Request \$request,\$record)
            {
            \$record = $name::findOrFail(\$record);

                \$validated = \$request->validate($name::rules());

                if (\$request->hasFile('image')) {
                    ImageHelper::removeImageInPublicDirectory(\$record->image);
                    \$validated['image'] = ImageHelper::storeImageInPublicDirectory(\$request->file('image'), 'uploads/{$routePrefix}');
                }

                if (\$request->hasFile('file')) {
                    \$validated['file'] = \$request->file('file')->store('uploads/{$routePrefix}', 'public');
                }

                if (\$request->hasFile('images')) {
                    if (\$record->images) {
                        foreach (json_decode(\$record->images, true) as \$oldImage) {
                            ImageHelper::removeImageInPublicDirectory(\$oldImage);
                        }
                    }
                    \$validated['images'] = [];
                    foreach (\$request->file('images') as \$image) {
                        \$validated['images'][] = ImageHelper::storeImageInPublicDirectory(\$image, 'uploads/{$routePrefix}');
                    }
                    \$validated['images'] = json_encode(\$validated['images']);
                }

                \$record->update(\$validated);
                return redirect()->route('{$routePrefix}.index')->with('success', 'تم التحديث بنجاح');
            }

            public function destroy(\$record)
            {\$record = $name::findOrFail(\$record);

                if (\$record->image) {
                    ImageHelper::removeImageInPublicDirectory(\$record->image);
                }

                if (\$record->images) {
                    foreach (json_decode(\$record->images, true) as \$image) {
                        ImageHelper::removeImageInPublicDirectory(\$image);
                    }
                }

                \$record->delete();
                return redirect()->route('{$routePrefix}.index')->with('success', 'تم الحذف بنجاح');
            }
        EOT;
        }

        $controllerContent .= "\n}\n";

        File::put($controllerPath, $controllerContent);

        echo "\033[32m Controller created: {$controllerPath} \033[0m\n";
    }
}
