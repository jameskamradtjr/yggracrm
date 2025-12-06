<?php

use Core\Migration;
use Core\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona campos para formas de pagamento
        $this->addColumn('proposal_conditions', 'tipo', 'VARCHAR(50)', ['nullable' => true, 'default' => null]); // 'pagamento' ou null (condição normal)
        $this->addColumn('proposal_conditions', 'valor_original', 'DECIMAL(15,2)', ['nullable' => true]);
        $this->addColumn('proposal_conditions', 'valor_final', 'DECIMAL(15,2)', ['nullable' => true]);
        $this->addColumn('proposal_conditions', 'parcelas', 'INTEGER', ['nullable' => true]);
        $this->addColumn('proposal_conditions', 'valor_parcela', 'DECIMAL(15,2)', ['nullable' => true]);
        $this->addColumn('proposal_conditions', 'is_selected', 'TINYINT(1)', ['nullable' => false, 'default' => 0]); // Se foi selecionado pelo cliente
        
        $this->addIndex('proposal_conditions', 'tipo');
    }

    public function down(): void
    {
        $this->dropIndex('proposal_conditions', 'tipo');
        $this->dropColumn('proposal_conditions', 'is_selected');
        $this->dropColumn('proposal_conditions', 'valor_parcela');
        $this->dropColumn('proposal_conditions', 'parcelas');
        $this->dropColumn('proposal_conditions', 'valor_final');
        $this->dropColumn('proposal_conditions', 'valor_original');
        $this->dropColumn('proposal_conditions', 'tipo');
    }
};

