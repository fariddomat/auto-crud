<?php

namespace Fariddomat\AutoCrud\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ViewGenerator
{
    public static function generateBladeViews($name, $isDashboard, $fields)
    {
        $folderName = $isDashboard ? 'dashboard.' . Str::plural(Str::snake($name)) : Str::plural(Str::snake($name));
        $basePath = resource_path("views/" . ($isDashboard ? "dashboard/" . Str::plural(Str::snake($name)) : Str::plural(Str::snake($name))));

        if (!File::exists($basePath)) {
            File::makeDirectory($basePath, 0755, true, true);
        }

        File::put("$basePath/create.blade.php", self::generateCreateBlade($folderName, $fields));
        File::put("$basePath/edit.blade.php", self::generateEditBlade($folderName, $fields));

        // تعديل `index` ليستخدم `x-autocrud::table`
        $indexView = <<<EOT
    <x-app-layout>
        <div class="container mx-auto p-6">
            <h1 class="text-2xl font-bold mb-4">$name</h1>
            <a href="{{ route('{$folderName}.create') }}" class="px-4 py-2 bg-blue-500 text-white rounded shadow" wire:navigate>➕ @lang('site.add') $name</a>

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
    }

    public static function generateCreateBlade($folderName, $fields)
    {
        $formFields = self::generateFormFields($fields);

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

    public static function generateEditBlade($folderName, $fields)
    {
        $formFields = self::generateFormFields($fields, true);

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

    public static function generateFormFields($fields, $isEdit = false)
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
}
