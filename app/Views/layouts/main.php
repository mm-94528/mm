<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= $this->e($title ?? config('app.name')) ?></title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <?php if ($this->hasSection('styles')): ?>
        <?php $this->yield('styles') ?>
    <?php endif; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <a href="<?= url('/') ?>"><?= config('app.name') ?></a>
            </div>
            
            <div class="navbar-menu">
                <?php if (auth()): ?>
                    <div class="navbar-start">
                        <a href="<?= url('/dashboard') ?>" class="navbar-item">Dashboard</a>
                        <a href="<?= url('/articles') ?>" class="navbar-item">Articoli</a>
                        <a href="<?= url('/customers') ?>" class="navbar-item">Clienti</a>
                        <?php if (auth()['role'] === 'admin'): ?>
                            <a href="<?= url('/admin') ?>" class="navbar-item">Admin</a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="navbar-end">
                        <div class="navbar-item has-dropdown">
                            <a class="navbar-link">
                                <?= $this->e(auth()['name']) ?>
                            </a>
                            <div class="navbar-dropdown">
                                <a href="<?= url('/profile') ?>" class="navbar-item">Profilo</a>
                                <hr class="navbar-divider">
                                <a href="<?= url('/logout') ?>" class="navbar-item" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    Logout
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="navbar-end">
                        <a href="<?= url('/login') ?>" class="navbar-item">Login</a>
                        <a href="<?= url('/register') ?>" class="navbar-item">Registrati</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Toast Container -->
    <div id="toast-container"></div>
    
    <!-- Main Content -->
    <main class="main-content">
        <?php $this->yield('content') ?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= config('app.name') ?>. Tutti i diritti riservati.</p>
        </div>
    </footer>
    
    <!-- Logout Form -->
    <?php if (auth()): ?>
        <form id="logout-form" action="<?= url('/logout') ?>" method="POST" style="display: none;">
            <?= csrf_field() ?>
        </form>
    <?php endif; ?>
    
    <!-- Scripts -->
    <script src="<?= asset('js/app.js') ?>"></script>
    <?php if ($this->hasSection('scripts')): ?>
        <?php $this->yield('scripts') ?>
    <?php endif; ?>
</body>
</html>