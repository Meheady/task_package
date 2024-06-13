<?php


namespace Package\Apicrud\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrudCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {name} {--columns=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CRUD. Example: php artisan make:crud {name} --columns=title:string,content:text,author_id:integer';

    /**
     * Execute the console command.
     *
     * @return int
     */
    protected $name;
    protected $namePlural;
    protected $route;
    protected $columns;

    public function handle()
    {
        $this->name = $this->argument('name'); // using this name create Controller, model, migration file name and classes

        $this->namePlural = Str::plural($this->name);
        $this->columns = $this->option('columns');
        $this->route = strtolower(Str::kebab($this->namePlural));

        // start Create Model
        $modelstub = $this->getModelStub();
        $modelstub =  $this->generateModelCode($modelstub);
        $this->makeModel($modelstub);
        // end create model

        // start Create Controller with crud methods
        $stub = $this->getStub();
        $stub = $this->generateCrudCode($stub);
        $this->makeController($stub);
        // end controller


        // generate migration file
        $migrationStub = $this->getMigrationStub();
        $migrationCode = $this->generateMigrationCode($migrationStub);
        $this->makeMigration($migrationCode);
        // end migration

        // create api resource route
        $routeStub = $this->getRouteStub();
        $routeCode = $this->generateRouteCode($routeStub);
        $this->addRoute($routeCode);
        // end route

        $this->info('Created Controller,Model,Migration, Route');
    }

    protected function generateCrudCode($stub)
    {
        $cruds = ["edit", "index", "show", "store", "update", "destroy"];
        $methods = [];
        $places = [];
        try {
            foreach ($cruds as $key => $crud) {
                $data = $this->{$crud}();
                $places[] = "{{ $crud }}";
                $methods[$key] = "$data";
            }
            $rootNamespace = $this->rootNamespace();
            $className = $this->name . "Controller";
            return $stub = str_replace(array_merge($places, ["{{ rootNamespace }}", "{{ class }}"]), array_merge($methods, [$rootNamespace, $className]), $stub);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function generateModelCode($stub)
    {
        try {
            $className = $this->name;  // $this->name is set to the class name you want to use
        return str_replace("{{ class }}", $className, $stub);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function generateMigrationCode($stub)
    {
        try {
            $className = $this->name;
            $classPlural = $this->namePlural;
            $tableName = strtolower($classPlural);

            $columnsCode = $this->generateMigrationColumnsCode();

            return str_replace(["{{ class }}", "{{ classPlural }}", "{{ table }}", "{{ columns }}"], [$className, $classPlural, $tableName, $columnsCode], $stub);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function generateMigrationColumnsCode()
    {
        $columns = $this->columns;
        $columnsArray = explode(',', $columns);
        $columnsCode = '';

        foreach ($columnsArray as $column) {
            list($name, $type) = explode(':', $column);
            $columnsCode .= "\$table->$type('$name');\n";
        }

        return $columnsCode;
    }

    protected function getStub()
    {
        return File::get(__DIR__ . "/../stubs/controller.stub");

    }
    protected function getModelStub()
    {
        return file_get_contents(__DIR__ . "/../stubs/model.stub");

    }
    protected function getMigrationStub()
    {
        return File::get(__DIR__ . "/../stubs/migration.stub");

    }
    protected function makeController($stub)
    {
        $directory = app_path('Http/Controllers/Api/V1');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $path = app_path('Http/Controllers/Api/V1/' . $this->name . 'Controller.php');

        File::put($path, $stub);
    }

    protected function makeModel($modelCode)
    {
        $path = app_path("Models/{$this->name}.php");
        file_put_contents($path, $modelCode);
    }

    protected function makeMigration($migrationCode)
    {
        $timestamp = date('Y_m_d_His');
        $path = database_path("migrations/{$timestamp}_create_{$this->namePlural}_table.php");
        file_put_contents($path, $migrationCode);
    }

    protected function getRouteStub()
    {
        return file_get_contents(__DIR__ . "/../stubs/route.stub");
    }

    protected function generateRouteCode($stub)
    {
        try {
            $className = $this->name;
            $routeName = $this->route;
            return str_replace(["{{ class }}", "{{ route }}"], [$className, $routeName], $stub);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    protected function addRoute($routeCode)
    {
        $path = base_path('routes/api.php');
        $existingContent = file_get_contents($path);

        $useStatement = "use App\Http\Controllers\Api\V1\\{$this->name}Controller;";
        if (strpos($existingContent, $useStatement) === false) {
            $existingContent = "<?php\n\n{$useStatement}\n" . substr($existingContent, 6);
        }
        $routeDefinition = "Route::apiResource('{$this->route}', {$this->name}Controller::class);";
        if (strpos($existingContent, $routeDefinition) === false) {
            $existingContent .= PHP_EOL . $routeDefinition;
        }

        file_put_contents($path, $existingContent);
    }

    protected function getColumns()
    {
        $model = "App\Models\\$this->name";
        if (class_exists($model)) {
            $model = new $model;
            return Schema::getColumnListing($model->getTable());
        }
        return $this->error('Model not found');
    }
    protected function store()
    {
        $validate = "";
        foreach ($this->getColumns() as $key => $value) {
            if ($value != 'id' && $value != 'updated_at' && $value != 'created_at'){
                $validate .= "'$value' => 'required',\n";
            }

        }
        $store = "\Illuminate\Support\Facades\Cache::forget('$this->name');\n";
        $store .= "\$validData=\$request->validate([" . $validate . "]);\n";
        $store .= "\App\Models\\$this->name::create(\$validData);\n";
        return $store;
    }
    protected function update()
    {
        $validate = "";
        foreach ($this->getColumns() as $key => $value) {

            if ($value !== 'id' && $value !== 'created_at' && $value !== 'updated_at'){
                $validate .= "'$value' => 'sometimes',\n";
            }

        }
        $update = "\Illuminate\Support\Facades\Cache::forget('$this->name');\n";
        $update .= "\$validData=\$request->validate([" . $validate . "]);\n";
        $update .= "\App\Models\\$this->name::where('id',\$id)->update(\$validData);\n";

        return $update;
    }
    protected function index()
    {
        $index = "\$index=\Illuminate\Support\Facades\Cache::remember('$this->name', 84000, function () {\n";
        $index .= "return \App\Models\\$this->name::all();\n";
        $index .= "});";
        return $index;
    }
    protected function show()
    {
        $show = "\$show=\App\Models\\$this->name::findOrFail(\$id);\n";
        return $show;
    }
    protected function edit()
    {
        $edit = "\$edit=\App\Models\\$this->name::find(\$id);\n";
        return $edit;
    }
    protected function destroy()
    {
        $delete = "\Illuminate\Support\Facades\Cache::forget('$this->name');\n";
        $delete .= "\App\Models\\$this->name::findOrFail(\$id)->delete();\n";
        return $delete;
    }
}
