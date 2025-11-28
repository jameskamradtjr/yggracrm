<?php

declare(strict_types=1);

namespace Core;

/**
 * Classe Validator - Validação de dados
 * 
 * Implementa validações comuns para formulários
 */
class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $validated = [];

    private array $messages = [
        'required' => 'O campo :field é obrigatório.',
        'email' => 'O campo :field deve ser um email válido.',
        'min' => 'O campo :field deve ter no mínimo :param caracteres.',
        'max' => 'O campo :field deve ter no máximo :param caracteres.',
        'numeric' => 'O campo :field deve ser um número.',
        'confirmed' => 'A confirmação do campo :field não confere.',
        'unique' => 'Este :field já está em uso.',
        'exists' => 'O :field selecionado é inválido.',
        'same' => 'O campo :field deve ser igual a :param.',
        'different' => 'O campo :field deve ser diferente de :param.',
        'in' => 'O campo :field deve ser um dos seguintes valores: :param.',
        'not_in' => 'O campo :field não pode ser um dos seguintes valores: :param.',
        'regex' => 'O formato do campo :field é inválido.',
        'alpha' => 'O campo :field deve conter apenas letras.',
        'alpha_num' => 'O campo :field deve conter apenas letras e números.',
        'url' => 'O campo :field deve ser uma URL válida.',
        'date' => 'O campo :field deve ser uma data válida.',
        'before' => 'O campo :field deve ser uma data anterior a :param.',
        'after' => 'O campo :field deve ser uma data posterior a :param.',
    ];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->validate();
    }

    /**
     * Executa a validação
     */
    private function validate(): void
    {
        foreach ($this->rules as $field => $rules) {
            $rulesArray = is_string($rules) ? explode('|', $rules) : $rules;
            
            $value = $this->data[$field] ?? null;
            $isNullable = in_array('nullable', $rulesArray);
            $hasRequired = in_array('required', $rulesArray);
            
            // Se o campo é nullable e está vazio, adiciona ao validated como null e pula validações
            if ($isNullable && empty($value) && !$hasRequired) {
                $this->validated[$field] = null;
                continue;
            }

            $fieldValidated = false;
            foreach ($rulesArray as $rule) {
                if ($rule === 'nullable') {
                    continue; // Pula a regra nullable na validação
                }
                $this->validateRule($field, $rule);
                $fieldValidated = true;
            }
            
            // Se o campo passou em todas as validações mas não foi adicionado ao validated, adiciona agora
            if ($fieldValidated && !isset($this->validated[$field]) && !isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    /**
     * Valida uma regra específica
     */
    private function validateRule(string $field, string $rule): void
    {
        // Separa a regra de seus parâmetros
        [$ruleName, $params] = array_pad(explode(':', $rule, 2), 2, null);

        $value = $this->data[$field] ?? null;

        // Se o campo não é obrigatório e está vazio, pula validação
        if ($ruleName !== 'required' && empty($value)) {
            return;
        }

        $method = 'validate' . str_replace('_', '', ucwords($ruleName, '_'));

        if (method_exists($this, $method)) {
            $valid = $this->$method($field, $value, $params);

            if (!$valid) {
                $this->addError($field, $ruleName, $params);
            } else {
                $this->validated[$field] = $value;
            }
        }
    }

    /**
     * Adiciona um erro
     */
    private function addError(string $field, string $rule, ?string $params = null): void
    {
        $message = $this->messages[$rule] ?? 'O campo :field é inválido.';
        $message = str_replace(':field', $field, $message);
        $message = str_replace(':param', $params ?? '', $message);

        $this->errors[$field][] = $message;
    }

    /**
     * Regras de validação
     */
    private function validateRequired(string $field, mixed $value, ?string $params): bool
    {
        return !empty($value) || $value === '0';
    }

    private function validateEmail(string $field, mixed $value, ?string $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin(string $field, mixed $value, ?string $params): bool
    {
        return strlen((string)$value) >= (int)$params;
    }

    private function validateMax(string $field, mixed $value, ?string $params): bool
    {
        return strlen((string)$value) <= (int)$params;
    }

    private function validateNumeric(string $field, mixed $value, ?string $params): bool
    {
        return is_numeric($value);
    }

    private function validateConfirmed(string $field, mixed $value, ?string $params): bool
    {
        $confirmField = $field . '_confirmation';
        return isset($this->data[$confirmField]) && $value === $this->data[$confirmField];
    }

    private function validateUnique(string $field, mixed $value, ?string $params): bool
    {
        [$table, $column] = explode(',', $params . ',' . $field);
        
        $db = Database::getInstance();
        $result = $db->queryOne(
            "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?",
            [$value]
        );

        return $result['count'] == 0;
    }

    private function validateExists(string $field, mixed $value, ?string $params): bool
    {
        [$table, $column] = explode(',', $params . ',' . $field);
        
        $db = Database::getInstance();
        $result = $db->queryOne(
            "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?",
            [$value]
        );

        return $result['count'] > 0;
    }

    private function validateSame(string $field, mixed $value, ?string $params): bool
    {
        return isset($this->data[$params]) && $value === $this->data[$params];
    }

    private function validateAlpha(string $field, mixed $value, ?string $params): bool
    {
        return preg_match('/^[a-zA-ZÀ-ÿ\s]+$/', $value) === 1;
    }

    private function validateAlphaNum(string $field, mixed $value, ?string $params): bool
    {
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }

    private function validateUrl(string $field, mixed $value, ?string $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    private function validateRegex(string $field, mixed $value, ?string $params): bool
    {
        return preg_match($params, $value) === 1;
    }

    private function validateIn(string $field, mixed $value, ?string $params): bool
    {
        if ($params === null) {
            return false;
        }
        
        $allowedValues = explode(',', $params);
        return in_array($value, $allowedValues);
    }

    private function validateDate(string $field, mixed $value, ?string $params): bool
    {
        if (empty($value)) {
            return true; // Campos nullable podem ser vazios
        }
        
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value;
    }

    private function validateInteger(string $field, mixed $value, ?string $params): bool
    {
        if (empty($value)) {
            return true; // Campos nullable podem ser vazios
        }
        
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Verifica se passou na validação
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Verifica se falhou na validação
     */
    public function fails(): bool
    {
        return !$this->passes();
    }

    /**
     * Retorna os erros
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Retorna os dados validados
     */
    public function validated(): array
    {
        return $this->validated;
    }
}

