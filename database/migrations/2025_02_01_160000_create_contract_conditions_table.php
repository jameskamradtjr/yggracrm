<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTable('contract_conditions', function (Schema $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('titulo');
            $table->text('descricao');
            $table->integer('ordem')->default(0);
            $table->timestamps();

            $table->index('contract_id');
            $table->index('ordem');
            
            $table->foreign('contract_id', 'id', 'contracts', 'CASCADE');
        });
    }

    public function down(): void
    {
        $this->dropTable('contract_conditions');
    }
};

