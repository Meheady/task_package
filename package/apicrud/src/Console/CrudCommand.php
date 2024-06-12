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
    protected $signature = 'make:crud {name} {--option=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new CRUD. Example: php artisan crud:make {name}=Model Name';

    /**
     * Execute the console command.
     *
     * @return int
     */
    protected $name;
protected $namePlural;
protected $route;

    public function handle()
    {
        $this->name = $this->argument('name');
        $this->namePlural = Str::plural($this->name);
        $this->route = strtolower(Str::kebab($this->namePlural));

        $modelstub = $this->getModelStub();
        $modelstub =  $this->generateModelCode($modelstub);
        $this->makeModel($modelstub);

        $stub = $this->getStub();
        $stub = $this->generateCrudCode($stub);
        $this->makeController($stub);


        $migrationStub = $this->getMigrationStub();
        $migrationCode = $this->generateMigrationCode($migrationStub);
        $this->makeMigration($migrationCode);

        $routeStub = $this->getRouteStub();
        $routeCode = $this->generateRouteCode($routeStub);
        $this->addRoute($routeCode);
        $this->info('Created Controllerc,Model,Migration, Route');

         $this->runMigration();
    }

    private function generateRoute($type,$contoller,$method){
        try {
            file_put_contents(
                base_path('routes/api.php'),
                "Route::$type(['$contoller','$method'])",
                FILE_APPEND
            );
            return true;
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }

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
            $className = $this->name;  // Assumes $this->name is set to the class name you want to use
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
            return str_replace(["{{ class }}", "{{ classPlural }}", "{{ table }}"], [$className, $classPlural, $tableName], $stub);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
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
        $path = app_path('Http/Controllers/' . $this->name . 'Controller.php');

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

        $useStatement = "use App\Http\Controllers\\{$this->name}Controller;";
        if (strpos($existingContent, $useStatement) === false) {
            $existingContent = "<?php\n\n{$useStatement}\n" . substr($existingContent, 6);
        }
        $routeDefinition = "Route::apiResource('{$this->route}', {$this->name}Controller::class);";
        if (strpos($existingContent, $routeDefinition) === false) {
            $existingContent .= PHP_EOL . $routeDefinition;
        }

        file_put_contents($path, $existingContent);
    }

    protected function runMigration()
    {
        \Artisan::call('migrate');
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
            $validate .= "'$value' => 'required',\n";
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
            $validate .= "'$value' => 'sometimes',\n";
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
