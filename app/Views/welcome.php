<?php $this->extends('layouts.main') ?>

<?php $this->section('content') ?>
<div class="hero">
    <div class="container">
        <h1 class="hero-title">Benvenuto in <?= config('app.name') ?></h1>
        <p class="hero-subtitle">
            Un framework PHP modulare e scalabile per i tuoi progetti
        </p>
        
        <div class="hero-buttons">
            <?php if (auth()): ?>
                <a href="<?= url('/dashboard') ?>" class="btn btn-primary">
                    Vai alla Dashboard
                </a>
            <?php else: ?>
                <a href="<?= url('/login') ?>" class="btn btn-primary">
                    Accedi
                </a>
                <a href="<?= url('/register') ?>" class="btn btn-secondary">
                    Registrati
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<section class="features">
    <div class="container">
        <h2 class="section-title">Caratteristiche Principali</h2>
        
        <div class="grid">
            <div class="card">
                <div class="card-icon">📦</div>
                <h3>Architettura Modulare</h3>
                <p>Aggiungi o rimuovi funzionalità facilmente con il sistema di moduli.</p>
            </div>
            
            <div class="card">
                <div class="card-icon">🚀</div>
                <h3>Performance Ottimizzate</h3>
                <p>Sistema di cache integrato e query ottimizzate per prestazioni elevate.</p>
            </div>
            
            <div class="card">
                <div class="card-icon">🔒</div>
                <h3>Sicurezza Integrata</h3>
                <p>Autenticazione, CSRF protection e validazione dati inclusi.</p>
            </div>
            
            <div class="card">
                <div class="card-icon">⚡</div>
                <h3>AJAX First</h3>
                <p>Interfaccia fluida con operazioni AJAX per una migliore UX.</p>
            </div>
        </div>
    </div>
</section>
<?php $this->endSection() ?>