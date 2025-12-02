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
        'min' => 'O campo :field deve ter no mínimo :param.',
        'max' => 'O campo :field deve ter no máximo :param.',
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
        
        // Ajusta mensagem de min/max baseado no tipo do campo
        if (in_array($rule, ['min', 'max']) && isset($this->data[$field])) {
            $value = $this->data[$field];
            if (is_numeric($value)) {
                // Se for numérico, usa mensagem numérica
                if ($rule === 'min') {
                    $message = "O campo :field deve ser no mínimo :param.";
                } else {
                    $message = "O campo :field deve ser no máximo :param.";
                }
            } else {
                // Se for string, usa mensagem de caracteres
                if ($rule === 'min') {
                    $message = "O campo :field deve ter no mínimo :param caracteres.";
                } else {
                    $message = "O campo :field deve ter no máximo :param caracteres.";
                }
            }
        }
        
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
        $minValue = (int)$params;
        
        // Se o valor é numérico, compara numericamente
        if (is_numeric($value)) {
            return (float)$value >= $minValue;
        }
        
        // Caso contrário, compara como string (comprimento)
        return strlen((string)$value) >= $minValue;
    }

    private function validateMax(string $field, mixed $value, ?string $params): bool
    {
        $maxValue = (int)$params;
        
        // Se o valor é numérico, compara numericamente
        if (is_numeric($value)) {
            return (float)$value <= $maxValue;
        }
        
        // Caso contrário, compara como string (comprimento)
        return strlen((string)$value) <= $maxValue;
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
        
        // Tenta formato datetime com segundos (Y-m-d\TH:i:s)
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:s', $value);
        if ($date !== false) {
            return true;
        }
        
        // Tenta formato datetime sem segundos (Y-m-d\TH:i)
        $date = \DateTime::createFromFormat('Y-m-d\TH:i', $value);
        if ($date !== false) {
            return true;
        }
        
        // Tenta formato date apenas (Y-m-d)
        $date = \DateTime::createFromFormat('Y-m-d', $value);
        if ($date !== false) {
            return true;
        }
        
        // Tenta formato datetime com timezone
        $date = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $value);
        if ($date !== false) {
            return true;
        }
        
        // Última tentativa: usar strtotime
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return true;
        }
        
        return false;
    }

    private function validateInteger(string $field, mixed $value, ?string $params): bool
    {
        if (empty($value)) {
            return true; // Campos nullable podem ser vazios
        }
        
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    private function validateBoolean(string $field, mixed $value, ?string $params): bool
    {
        if (empty($value) || $value === null) {
            return true; // Campos nullable podem ser vazios
        }
        
        // Aceita: true, false, 1, 0, '1', '0', 'true', 'false', 'on', 'off'
        if (is_bool($value)) {
            return true;
        }
        
        if (is_numeric($value)) {
            return in_array((int)$value, [0, 1]);
        }
        
        if (is_string($value)) {
            $lower = strtolower($value);
            return in_array($lower, ['true', 'false', '1', '0', 'on', 'off', 'yes', 'no']);
        }
        
        return false;
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

