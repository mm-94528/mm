<?php

namespace App\Cli\Commands;

class MakeModule extends \Command
{
    public function getName(): string
    {
        return 'make:module';
    }
    
    public function getDescription(): string
    {
        return 'Create a new module';
    }
    
    public function execute(array $args): void
    {
        if (empty($args) || $this->hasOption($args, 'help')) {
            $this->showHelp();
            return;
        }
        
        $moduleName = ucfirst($args[0]);
        $modulePath = APP_PATH . '/Modules/' . $moduleName;
        
        if (is_dir($modulePath)) {
            $this->error("Module '{$moduleName}' already exists!");
            return;
        }
        
        $this->info("Creating module: {$moduleName}");
        
        // Create module structure
        $this->createDirectory($modulePath);
        $this->createDirectory($modulePath . '/Controllers');
        $this->createDirectory($modulePath . '/Models');
        $this->createDirectory($modulePath . '/Views');
        $this->createDirectory($modulePath . '/Database/Migrations');
        $this->createDirectory($modulePath . '/Database/Seeders');
        
        // Create module.json
        $this->createModuleConfig($modulePath, $moduleName);
        
        // Create routes file
        $this->createRoutesFile($modulePath, $moduleName);
        
        // Create sample controller
        $this->createSampleController($modulePath, $moduleName);
        
        // Create sample model
        $this->createSampleModel($modulePath, $moduleName);
        
        // Create sample view
        $this->createSampleView($modulePath, $moduleName);
        
        // Update modules config
        $this->updateModulesConfig($moduleName);
        
        $this->info("Module '{$moduleName}' created successfully!");
        $this->line("\nTo enable the module, run:");
        $this->line("  php cli/console.php module:enable {$moduleName}");
    }
    
    private function showHelp(): void
    {
        $this->line("Usage: make:module [ModuleName]");
        $this->line("\nCreate a new module with the given name.");
        $this->line("\nExample:");
        $this->line("  php cli/console.php make:module Products");
    }
    
    private function createDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
    
    private function createModuleConfig(string $path, string $name): void
    {
        $content = json_encode([
            'name' => $name,
            'description' => "{$name} module",
            'version' => '1.0.0',
            'author' => 'Your Name',
            'dependencies' => []
        ], JSON_PRETTY_PRINT);
        
        file_put_contents($path . '/module.json', $content);
    }
    
    private function createRoutesFile(string $path, string $name): void
    {
        $content = <<<PHP
<?php

use App\Core\Router;
use App\Modules\\{$name}\\Controllers\\{$name}Controller;

/** @var Router \$router */

\$router->group(['prefix' => '/' . strtolower('{$name}'), 'middleware' => [\App\Core\Middleware\AuthMiddleware::class]], function (Router \$router) {
    \$router->get('/', [{$name}Controller::class, 'index']);
    \$router->get('/create', [{$name}Controller::class, 'create']);
    \$router->post('/', [{$name}Controller::class, 'store']);
    \$router->get('/{id}/edit', [{$name}Controller::class, 'edit']);
    \$router->put('/{id}', [{$name}Controller::class, 'update']);
    \$router->delete('/{id}', [{$name}Controller::class, 'destroy']);
});
PHP;
        
        file_put_contents($path . '/routes.php', $content);
    }
    
    private function createSampleController(string $path, string $name): void
    {
        $modelName = rtrim($name, 's');
        $content = <<<PHP
<?php

namespace App\Modules\\{$name}\\Controllers;

use App\Core\Controller;
use App\Modules\\{$name}\\Models\\{$modelName};

class {$name}Controller extends Controller
{
    public function index()
    {
        \$items = {$modelName}::all();
        
        if (\$this->isAjax()) {
            return \$this->json(['items' => \$items]);
        }
        
        return \$this->view('{$name}::index', compact('items'));
    }
    
    public function create()
    {
        return \$this->view('{$name}::create');
    }
    
    public function store()
    {
        \$input = \$this->getUserInput();
        
        // Add validation here
        
        \$item = {$modelName}::create(\$input);
        
        if (\$item) {
            return \$this->success(\$item, 'Creato con successo');
        }
        
        return \$this->error('Errore durante la creazione');
    }
    
    public function edit(\$id)
    {
        \$item = {$modelName}::find(\$id);
        
        if (!\$item) {
            return \$this->error('Non trovato', 404);
        }
        
        return \$this->view('{$name}::edit', compact('item'));
    }
    
    public function update(\$id)
    {
        \$item = {$modelName}::find(\$id);
        
        if (!\$item) {
            return \$this->error('Non trovato', 404);
        }
        
        \$input = \$this->getUserInput();
        
        // Add validation here
        
        \$item->fill(\$input);
        
        if (\$item->save()) {
            return \$this->success(\$item, 'Aggiornato con successo');
        }
        
        return \$this->error('Errore durante l\'aggiornamento');
    }
    
    public function destroy(\$id)
    {
        \$item = {$modelName}::find(\$id);
        
        if (!\$item) {
            return \$this->error('Non trovato', 404);
        }
        
        if (\$item->delete()) {
            return \$this->success(null, 'Eliminato con successo');
        }
        
        return \$this->error('Errore durante l\'eliminazione');
    }
}
PHP;
        
        file_put_contents($path . '/Controllers/' . $name . 'Controller.php', $content);
    }
    
    private function createSampleModel(string $path, string $name): void
    {
        $modelName = rtrim($name, 's');
        $tableName = strtolower($name);
        
        $content = <<<PHP
<?php

namespace App\Modules\\{$name}\\Models;

use App\Core\Model;

class {$modelName} extends Model
{
    protected string \$table = '{$tableName}';
    
    protected array \$fillable = [
        'name',
        'description'
    ];
}
PHP;
        
        file_put_contents($path . '/Models/' . $modelName . '.php', $content);
    }
    
    private function createSampleView(string $path, string $name): void
    {
        $content = <<<PHP
<?php \$this->extends('layouts.main') ?>

<?php \$this->section('content') ?>
<div class="container">
    <h1>{$name}</h1>
    
    <div class="mb-3">
        <a href="<?= url('/" . strtolower($name) . "/create') ?>" class="btn btn-primary">
            Nuovo
        </a>
    </div>
    
    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (\$items as \$item): ?>
                <tr>
                    <td><?= \$item->id ?></td>
                    <td><?= \$this->e(\$item->name) ?></td>
                    <td>
                        <a href="<?= url('/" . strtolower($name) . "/' . \$item->id . '/edit') ?>" class="btn btn-sm btn-primary">
                            Modifica
                        </a>
                        <button data-delete="<?= url('/" . strtolower($name) . "/' . \$item->id) ?>" class="btn btn-sm btn-danger">
                            Elimina
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php \$this->endSection() ?>
PHP;
        
        file_put_contents($path . '/Views/index.php', $content);
    }
    
    private function updateModulesConfig(string $name): void
    {
        $configFile = CONFIG_PATH . '/modules.php';
        $config = require $configFile;
        
        // Module is created but not enabled by default
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($configFile, $content);
    }
}