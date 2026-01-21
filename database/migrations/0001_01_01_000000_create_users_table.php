<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // clé primaire auto-incrémentée
            $table->string('name'); // nom de l'étudiant
            $table->string('email')->unique(); // email unique
            $table->timestamp('email_verified_at')->nullable(); // vérification email
            $table->string('password'); // mot de passe
            $table->string('filiere')->nullable(); // filière (optionnel)
            $table->string('annee')->nullable(); // année d'étude (optionnel)
            $table->rememberToken(); // token pour "remember me"
            $table->timestamps(); // created_at et updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
