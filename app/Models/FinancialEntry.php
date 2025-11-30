<?php

declare(strict_types=1);

namespace App\Models;

use Core\Model;

class FinancialEntry extends Model
{
    protected string $table = 'financial_entries';
    protected bool $multiTenant = true;
    
    protected array $fillable = [
        'type', 'description', 'competence_date', 'due_date', 'payment_date',
        'release_date', 'value', 'paid_value', 'fees', 'interest',
        'bank_account_id', 'credit_card_id', 'supplier_id', 'client_id',
        'category_id', 'subcategory_id', 'cost_center_id', 'sub_cost_center_id',
        'is_paid', 'is_received', 'is_recurring', 'is_installment',
        'installment_number', 'total_installments', 'parent_entry_id',
        'recurrence_type', 'recurrence_end_date', 'observations', 'attachments',
        'responsible_user_id', 'user_id', 'payment_method_id', 'data_liberacao'
    ];
    
    protected array $casts = [
        'value' => 'float',
        'paid_value' => 'float',
        'fees' => 'float',
        'interest' => 'float',
        'is_paid' => 'boolean',
        'is_received' => 'boolean',
        'is_recurring' => 'boolean',
        'is_installment' => 'boolean',
        'attachments' => 'json'
    ];
    
    /**
     * Relacionamento com forma de pagamento
     */
    public function paymentMethod()
    {
        if (!$this->payment_method_id) {
            return null;
        }
        return PaymentMethod::find($this->payment_method_id);
    }
    
    /**
     * Retorna tags do lançamento
     */
    public function getTags(): array
    {
        $entryTags = \Core\Database::getInstance()->query(
            "SELECT t.* FROM tags t 
             INNER JOIN financial_entry_tags fet ON t.id = fet.tag_id 
             WHERE fet.financial_entry_id = ?",
            [$this->id]
        );
        
        return $entryTags ?: [];
    }
    
    /**
     * Adiciona uma tag ao lançamento
     */
    public function addTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "INSERT INTO financial_entry_tags (financial_entry_id, tag_id, created_at, updated_at) 
                 VALUES (?, ?, NOW(), NOW())",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Remove uma tag do lançamento
     */
    public function removeTag(int $tagId): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "DELETE FROM financial_entry_tags 
                 WHERE financial_entry_id = ? AND tag_id = ?",
                [$this->id, $tagId]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Remove todas as tags do lançamento
     */
    public function removeAllTags(): bool
    {
        $db = \Core\Database::getInstance();
        try {
            $db->execute(
                "DELETE FROM financial_entry_tags 
                 WHERE financial_entry_id = ?",
                [$this->id]
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Marca como pago/recebido
     */
    public function markAsPaid(?string $paymentDate = null): bool
    {
        $updateData = [
            'is_paid' => $this->type === 'saida' ? true : $this->is_paid,
            'is_received' => $this->type === 'entrada' ? true : $this->is_received,
            'payment_date' => $paymentDate ?? date('Y-m-d')
        ];
        
        if ($this->type === 'saida' && !$this->paid_value) {
            $updateData['paid_value'] = $this->value;
        } elseif ($this->type === 'entrada' && !$this->paid_value) {
            $updateData['paid_value'] = $this->value;
        }
        
        return $this->update($updateData);
    }
    
    /**
     * Desmarca como pago/recebido
     */
    public function unmarkAsPaid(): bool
    {
        return $this->update([
            'is_paid' => false,
            'is_received' => false,
            'payment_date' => null,
            'paid_value' => null
        ]);
    }
}

