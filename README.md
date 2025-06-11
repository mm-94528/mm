PHP Modular Scaffold
Un framework PHP modulare e scalabile per lo sviluppo rapido di applicazioni web.

Caratteristiche Principali
🏗️ Architettura MVC - Pattern Model-View-Controller ben strutturato
📦 Sistema Modulare - Aggiungi e rimuovi funzionalità facilmente
⚡ AJAX First - Tutte le operazioni CRUD via AJAX per un'esperienza fluida
🔒 Autenticazione Integrata - Sistema di login/registrazione completo
🗄️ ORM Semplice - Query builder e modelli con relazioni
💾 Sistema di Cache - Cache file-based integrata
📬 Queue System - Gestione job asincroni
🛠️ CLI Tools - Comandi per generazione codice e gestione
🔐 Sicurezza - CSRF protection, password hashing, validazione input
Requisiti
PHP 8.1 o superiore
MySQL 5.7 o superiore
Composer
Apache/Nginx con mod_rewrite
Installazione
Clona il repository:
bash
git clone https://github.com/tuouser/modular-scaffold.git
cd modular-scaffold
Installa le dipendenze:
bash
composer install
Copia il file di configurazione:
bash
cp .env.example .env
Configura il database nel file .env:
env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=scaffold_db
DB_USERNAME=root
DB_PASSWORD=
Crea il database e lancia le migrazioni:
bash
mysql -u root -p -e "CREATE DATABASE scaffold_db"
php cli/console.php migrate
Configura il web server per puntare alla cartella public/
Accedi all'applicazione e usa le credenziali di default:
Email: admin@example.com
Password: admin123
Struttura del Progetto
project/
├── app/                    # Codice dell'applicazione
│   ├── Core/              # Classi core del framework
│   ├── Helpers/           # Funzioni helper
│   └── Modules/           # Moduli dell'applicazione
├── config/                # File di configurazione
├── database/              # Migrazioni e seeds
├── public/                # Web root pubblica
│   ├── index.php         # Entry point
│   └── assets/           # CSS, JS, immagini
├── storage/               # File storage
│   ├── cache/            # File di cache
│   └── logs/             # Log files
└── cli/                   # CLI tools
Comandi CLI
Crea un nuovo modulo
bash
php cli/console.php make:module NomeModulo
Esegui le migrazioni
bash
php cli/console.php migrate
Avvia il queue worker
bash
php cli/console.php queue:work
Creazione di un Modulo
I moduli sono autocontenuti e includono:

Controllers
Models
Views
Routes
Migrazioni
Per creare un nuovo modulo:

bash
php cli/console.php make:module Products
Questo creerà la struttura:

app/Modules/Products/
├── Controllers/
├── Models/
├── Views/
├── Database/
│   ├── Migrations/
│   └── Seeders/
├── routes.php
└── module.json
Routing
Le route sono definite nei file routes.php:

php
// GET route
$router->get('/products', [ProductsController::class, 'index']);

// POST route
$router->post('/products', [ProductsController::class, 'store']);

// Route con parametri
$router->get('/products/{id}', [ProductsController::class, 'show']);

// Gruppo di route
$router->group(['prefix' => '/admin', 'middleware' => [AuthMiddleware::class]], function ($router) {
    $router->get('/dashboard', [AdminController::class, 'dashboard']);
});
Models
I modelli estendono la classe base Model:

php
namespace App\Modules\Products\Models;

use App\Core\Model;

class Product extends Model
{
    protected string $table = 'products';
    
    protected array $fillable = [
        'name', 'price', 'description'
    ];
    
    // Relazioni
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
Controllers
I controller gestiscono la logica:

php
namespace App\Modules\Products\Controllers;

use App\Core\Controller;

class ProductsController extends Controller
{
    public function index()
    {
        $products = Product::all();
        
        if ($this->isAjax()) {
            return $this->json(['products' => $products]);
        }
        
        return $this->view('Products::index', compact('products'));
    }
}
AJAX Operations
Tutte le form con attributo data-ajax="true" vengono inviate via AJAX:

html
<form action="/products" method="POST" data-ajax="true">
    <!-- campi del form -->
</form>
JavaScript helper per operazioni AJAX:

javascript
// Mostra toast notification
App.toast('Messaggio', 'success');

// Carica contenuto via AJAX
App.loadContent('/products', '#container');

// Delete con conferma
<button data-delete="/products/1">Elimina</button>
Sistema di Cache
php
// Salva in cache
cache()->put('key', $value, 3600); // 1 ora

// Recupera dalla cache
$value = cache()->get('key', 'default');

// Remember pattern
$products = cache()->remember('products', 3600, function() {
    return Product::all();
});
Queue System
Per creare un job:

php
class SendEmailJob extends Job
{
    public function handle(array $data): void
    {
        // Logica per inviare email
        Mail::send($data['to'], $data['subject'], $data['body']);
    }
}

// Aggiungi alla coda
$queue = new Queue();
$queue->push(SendEmailJob::class, [
    'to' => 'user@example.com',
    'subject' => 'Welcome',
    'body' => 'Email content'
]);
Sicurezza
CSRF Protection: Automatica per tutte le POST/PUT/DELETE
XSS Protection: Usa $this->e() nelle view per escape
SQL Injection: Query preparate con PDO
Password Hashing: Bcrypt con password_hash()
Best Practices
Moduli: Mantieni i moduli indipendenti e riutilizzabili
Validazione: Valida sempre l'input utente nei controller
Cache: Usa la cache per query costose
Queue: Usa le code per operazioni lunghe
AJAX: Fornisci sempre feedback visuale per le operazioni
Licenza
Questo progetto è rilasciato sotto licenza MIT.

