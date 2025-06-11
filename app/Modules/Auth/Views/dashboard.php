<?php $this->extends('layouts.main') ?>

<?php $this->section('content') ?>
<div class="container">
    <h1>Dashboard</h1>
    <p>Benvenuto, <?= $this->e(auth()['name']) ?>!</p>
    
    <div class="grid">
        <div class="card">
            <div class="card-icon">📝</div>
            <h3>Articoli</h3>
            <p class="text-primary" style="font-size: 2rem;"><?= $stats['total_articles'] ?></p>
            <a href="<?= url('/articles') ?>" class="btn btn-sm btn-primary">Gestisci</a>
        </div>
        
        <div class="card">
            <div class="card-icon">👥</div>
            <h3>Clienti</h3>
            <p class="text-primary" style="font-size: 2rem;"><?= $stats['total_customers'] ?></p>
            <a href="<?= url('/customers') ?>" class="btn btn-sm btn-primary">Gestisci</a>
        </div>
        
        <div class="card">
            <div class="card-icon">⚙️</div>
            <h3>Impostazioni</h3>
            <p>Gestisci il tuo profilo</p>
            <a href="<?= url('/profile') ?>" class="btn btn-sm btn-secondary">Profilo</a>
        </div>
    </div>
    
    <?php if (!empty($stats['recent_articles'])): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Articoli Recenti</h3>
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th>Titolo</th>
                    <th>Stato</th>
                    <th>Data</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['recent_articles'] as $article): ?>
                <tr>
                    <td><?= $this->e($article['title']) ?></td>
                    <td>
                        <span class="badge badge-<?= $article['status'] === 'published' ? 'success' : 'secondary' ?>">
                            <?= ucfirst($article['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($article['created_at'])) ?></td>
                    <td>
                        <a href="<?= url('/articles/' . $article['id'] . '/edit') ?>" class="btn btn-sm btn-primary">
                            Modifica
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<style>
.badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: var(--border-radius);
}

.badge-success {
    background-color: var(--success-color);
    color: white;
}

.badge-secondary {
    background-color: var(--secondary-color);
    color: white;
}
</style>
<?php $this->endSection() ?>