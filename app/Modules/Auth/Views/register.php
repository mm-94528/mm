<?php $this->extends('layouts.main') ?>

<?php $this->section('content') ?>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Registrati</h2>
                </div>
                
                <form action="<?= url('/register') ?>" method="POST" data-ajax="true">
                    <?= csrf_field() ?>
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Nome</label>
                        <input type="text" 
                               class="form-control" 
                               id="name" 
                               name="name" 
                               value="<?= old('name') ?>" 
                               required 
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?= old('email') ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required 
                               minlength="8">
                        <small class="text-muted">Minimo 8 caratteri</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Conferma Password</label>
                        <input type="password" 
                               class="form-control" 
                               id="password_confirmation" 
                               name="password_confirmation" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            Registrati
                        </button>
                    </div>
                    
                    <div class="text-center">
                        Hai già un account? <a href="<?= url('/login') ?>">Accedi</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php $this->endSection() ?>