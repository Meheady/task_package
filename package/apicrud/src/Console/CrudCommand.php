<?php


namespace Package\Apicrud\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

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


    public function handle()
    {
        $this->name = $this->argument('name');
        $stub = $this->getControllerStub();
        $stub = $this->generateCrudCode($stub);
        $this->makeController($stub);

        $this->info('Created CRUD...');
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

            $rootNamespace = $this->rootNamespace();
            $className = $this->name;
            return $stub = str_replace(array_merge($places, ["{{ rootNamespace }}", "{{ class }}"]), array_merge($methods, [$rootNamespace, $className]), $stub);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    protected function getControllerStub()
    {
        return File::get(__DIR__ . "/../stubs/controller.stub");

    }
    protected function getModelStub()
    {
        return File::get(__DIR__ . "/../stubs/model.stub");

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
