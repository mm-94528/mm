<?php $this->extends('layouts.main') ?>

<?php $this->section('content') ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Accedi</h2>
                </div>
                
                <form action="<?= url('/login') ?>" method="POST" data-ajax="true">
                    <?= csrf_field() ?>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?= old('email') ?>" 
                               required 
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="remember" value="1">
                            Ricordami
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            Accedi
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <a href="<?= url('/password/reset') ?>">Password dimenticata?</a>
                        <br>
                        Non hai un account? <a href="<?= url('/register') ?>">Registrati</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.row {
    display: flex;
    margin: -1rem;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding: 1rem;
}

.justify-content-center {
    justify-content: center;
}

@media (max-width: 768px) {
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>
<?php $this->endSection() ?>