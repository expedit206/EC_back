<?php
// database/migrations/2025_07_01_000001_create_users_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Laravel sait déjà que c'est la clé primaire
            $table->string('nom');
            $table->string('email')->nullable()->unique();
            $table->string('telephone')->unique();
            $table->string('ville');
            $table->string('mot_de_passe');
            $table->string('photo')->nullable();
            $table->boolean('premium')->default(false);

            // Corrigé ici :
            $table->foreignId('parrain_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('code_parrainage')->unique()->nullable();
            $table->decimal('solde', 10, 2)->default(0);
            $table->string('token')->nullable()->unique();
            $table->timestamps();
        });
        
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');


        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
}