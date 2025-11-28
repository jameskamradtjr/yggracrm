<?php

return new class extends \Core\Migration {
    public function up(): void
    {
        $this->createTable('lead_origins', function (\Core\Schema $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Multi-tenancy
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->integer('ordem')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('ativo');
        });
    }

    public function down(): void
    {
        $this->dropTable('lead_origins');
    }
};

