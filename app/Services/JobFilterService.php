<?php

namespace App\Services;

use App\Models\Attribute;
use App\Models\JobListing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class JobFilterService
{
    /**
     * Base query builder instance for job listings
     *
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;
    
    /**
     * Initialize a new JobFilterService instance.
     */
    public function __construct()
    {
        $this->query = JobListing::query();
    }
    
    /**
     * Apply filters to the query.
     *
     * @param string|null $filterString The filter string from the query parameter
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function applyFilters(?string $filterString): Builder
    {
        if (empty($filterString)) {
            return $this->query;
        }
        
        $parsed = $this->parseFilterString($filterString);
        
        return $this->buildQuery($parsed);
    }
    
    /**
     * Parse the filter string into a structured format.
     *
     * @param string $filterString
     * @return array
     */
    protected function parseFilterString(string $filterString): array
    {
        $result = [];
        
        // Remove outer parentheses if they exist
        $filterString = trim($filterString);
        if (substr($filterString, 0, 1) === '(' && substr($filterString, -1) === ')') {
            $filterString = substr($filterString, 1, -1);
        }
        
        // Split by the top-level AND/OR operators
        $conditions = $this->splitByLogicalOperators($filterString);
        
        if (count($conditions) === 1 && !isset($conditions['operator'])) {
            // Single condition
            return $this->parseCondition($conditions[0]);
        }
        
        $operator = $conditions['operator'];
        unset($conditions['operator']);
        
        $parsed = [
            'operator' => $operator,
            'conditions' => [],
        ];
        
        foreach ($conditions as $condition) {
            if (substr($condition, 0, 1) === '(' && substr($condition, -1) === ')') {
                // Nested group
                $parsed['conditions'][] = $this->parseFilterString(substr($condition, 1, -1));
            } else {
                // Simple condition
                $parsed['conditions'][] = $this->parseCondition($condition);
            }
        }
        
        return $parsed;
    }
    
    /**
     * Split a filter string by logical operators (AND/OR) at the top level.
     *
     * @param string $filterString
     * @return array
     */
    protected function splitByLogicalOperators(string $filterString): array
    {
        $result = [];
        $current = '';
        $parenDepth = 0;
        $operator = null;
        
        $tokens = preg_split('/(AND|OR|\(|\))/', $filterString, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        foreach ($tokens as $token) {
            $token = trim($token);
            if (empty($token)) continue;
            
            if ($token === '(') {
                $parenDepth++;
                $current .= '(';
            } elseif ($token === ')') {
                $parenDepth--;
                $current .= ')';
            } elseif (($token === 'AND' || $token === 'OR') && $parenDepth === 0) {
                if (!empty($current)) {
                    $result[] = trim($current);
                    $current = '';
                }
                $operator = $operator ?? $token;
                if ($operator !== $token) {
                    // Mixed operators at the same level not supported - would need to restructure
                    throw new \InvalidArgumentException('Mixed AND/OR operators at the same level are not supported');
                }
            } else {
                $current .= ' ' . $token;
            }
        }
        
        if (!empty($current)) {
            $result[] = trim($current);
        }
        
        if (!empty($result) && $operator) {
            $result['operator'] = $operator;
        }
        
        return $result;
    }
    
    /**
     * Parse a single condition string.
     *
     * @param string $condition
     * @return array
     */
    protected function parseCondition(string $condition): array
    {
        $condition = trim($condition);
        
        // First, check if this is a relationship condition
        if (Str::contains($condition, ' HAS_ANY ')) {
            return $this->parseRelationshipCondition($condition, 'HAS_ANY');
        } elseif (Str::contains($condition, ' IS_ANY ')) {
            return $this->parseRelationshipCondition($condition, 'IS_ANY');
        } elseif (Str::contains($condition, ' EXISTS')) {
            return $this->parseExistsCondition($condition);
        }
        
        // Check if this is an attribute condition
        if (Str::startsWith($condition, 'attribute:')) {
            return $this->parseAttributeCondition($condition);
        }
        
        // Parse standard field conditions
        $operators = ['>=', '<=', '<>', '!=', '=', '>', '<', 'LIKE'];
        
        foreach ($operators as $op) {
            if (Str::contains($condition, " $op ")) {
                list($field, $value) = explode(" $op ", $condition, 2);
                
                // Handle IN operator with multiple values
                if ($op === '=' && Str::contains($value, '(') && Str::contains($value, ')')) {
                    return $this->parseInOperatorCondition($field, $value);
                }
                
                return [
                    'type' => 'field',
                    'field' => trim($field),
                    'operator' => trim($op),
                    'value' => $this->cleanValue($value),
                ];
            }
        }
        
        // If no operator found, assume equals by default
        if (Str::contains($condition, ' ')) {
            list($field, $value) = explode(' ', $condition, 2);
            return [
                'type' => 'field',
                'field' => trim($field),
                'operator' => '=',
                'value' => $this->cleanValue($value)
            ];
        }
        
        throw new \InvalidArgumentException("Invalid condition format: $condition");
    }
    
    /**
     * Parse a relationship condition like "languages HAS_ANY (PHP, JavaScript)".
     *
     * @param string $condition
     * @param string $operator
     * @return array
     */
    protected function parseRelationshipCondition(string $condition, string $operator): array
    {
        list($relation, $valueStr) = explode(" $operator ", $condition, 2);
        $relation = trim($relation);
        
        // Extract values from parentheses
        preg_match('/\((.*?)\)/', $valueStr, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException("Invalid format for $operator condition: $condition");
        }
        
        $values = array_map('trim', explode(',', $matches[1]));
        
        return [
            'type' => 'relation',
            'relation' => $relation,
            'operator' => $operator,
            'values' => $values,
        ];
    }
    
    /**
     * Parse an EXISTS relationship condition.
     *
     * @param string $condition
     * @return array
     */
    protected function parseExistsCondition(string $condition): array
    {
        $relation = trim(str_replace(' EXISTS', '', $condition));
        
        return [
            'type' => 'relation',
            'relation' => $relation,
            'operator' => 'EXISTS',
            'values' => [],
        ];
    }
    
    /**
     * Parse an IN operator condition like "job_type = (full-time, part-time)".
     *
     * @param string $field
     * @param string $valueStr
     * @return array
     */
    protected function parseInOperatorCondition(string $field, string $valueStr): array
    {
        // Extract values from parentheses
        preg_match('/\((.*?)\)/', $valueStr, $matches);
        if (empty($matches)) {
            throw new \InvalidArgumentException("Invalid format for IN condition: $field = $valueStr");
        }
        
        $values = array_map('trim', explode(',', $matches[1]));
        
        return [
            'type' => 'field',
            'field' => trim($field),
            'operator' => 'IN',
            'value' => $values,
        ];
    }
    
    /**
     * Parse an attribute condition like "attribute:years_experience >= 3".
     *
     * @param string $condition
     * @return array
     */
    protected function parseAttributeCondition(string $condition): array
    {
        $attributePart = Str::after($condition, 'attribute:');
        
        $operators = ['>=', '<=', '<>', '!=', '=', '>', '<', 'LIKE'];
        foreach ($operators as $op) {
            if (Str::contains($attributePart, " $op ")) {
                list($name, $value) = explode(" $op ", $attributePart, 2);
                
                // Handle IN operator with multiple values
                if ($op === '=' && Str::contains($value, '(') && Str::contains($value, ')')) {
                    $parsed = $this->parseInOperatorCondition($name, $value);
                    $parsed['type'] = 'attribute';
                    return $parsed;
                }
                
                return [
                    'type' => 'attribute',
                    'name' => trim($name),
                    'operator' => trim($op),
                    'value' => $this->cleanValue($value),
                ];
            }
        }
        
        throw new \InvalidArgumentException("Invalid attribute condition format: $condition");
    }
    
    /**
     * Clean values from quotes and other formatting.
     *
     * @param string $value
     * @return string
     */
    protected function cleanValue(string $value): string
    {
        $value = trim($value);
        
        // Remove quotes if present
        if ((Str::startsWith($value, "'") && Str::endsWith($value, "'")) || 
            (Str::startsWith($value, '"') && Str::endsWith($value, '"'))) {
            $value = substr($value, 1, -1);
        }
        
        return $value;
    }
    
    /**
     * Build the query based on the parsed filter conditions.
     *
     * @param array $parsed
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildQuery(array $parsed): Builder
    {
        if (isset($parsed['type'])) {
            // This is a leaf condition
            return $this->applyCondition($this->query, $parsed);
        }
        
        $method = strtolower($parsed['operator']) === 'and' ? 'where' : 'orWhere';
        
        foreach ($parsed['conditions'] as $condition) {
            $this->query->$method(function ($subQuery) use ($condition) {
                return $this->buildSubQuery($subQuery, $condition);
            });
        }
        
        return $this->query;
    }
    
    /**
     * Build a sub-query for nested conditions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $subQuery
     * @param array $condition
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildSubQuery(Builder $subQuery, array $condition): Builder
    {
        if (isset($condition['type'])) {
            // This is a leaf condition
            return $this->applyCondition($subQuery, $condition);
        }
        
        $method = strtolower($condition['operator']) === 'and' ? 'where' : 'orWhere';
        
        foreach ($condition['conditions'] as $subCondition) {
            $subQuery->$method(function ($q) use ($subCondition) {
                return $this->buildSubQuery($q, $subCondition);
            });
        }
        
        return $subQuery;
    }
    
    /**
     * Apply a single condition to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $condition
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyCondition(Builder $query, array $condition): Builder
    {
        switch ($condition['type']) {
            case 'field':
                return $this->applyFieldCondition($query, $condition);
            case 'relation':
                return $this->applyRelationCondition($query, $condition);
            case 'attribute':
                return $this->applyAttributeCondition($query, $condition);
            default:
                throw new \InvalidArgumentException("Unknown condition type: {$condition['type']}");
        }
    }
    
    /**
     * Apply a field-based condition to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $condition
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyFieldCondition(Builder $query, array $condition): Builder
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        if ($operator === 'IN') {
            return $query->whereIn($field, $value);
        } elseif ($operator === 'LIKE') {
            // Add wildcards for LIKE operator if not already present
            if (!Str::startsWith($value, '%') && !Str::endsWith($value, '%')) {
                $value = "%$value%";
            }
        }
        
        return $query->where($field, $operator, $value);
    }
    
    /**
     * Apply a relationship-based condition to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $condition
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyRelationCondition(Builder $query, array $condition): Builder
    {
        $relation = $condition['relation'];
        $operator = $condition['operator'];
        $values = $condition['values'];
        
        switch ($operator) {
            case 'HAS_ANY':
                return $query->whereHas($relation, function ($subQuery) use ($values) {
                    $subQuery->whereIn('name', $values);
                });
            case 'IS_ANY':
                // For locations, we need to handle special cases
                if ($relation === 'locations') {
                    return $query->where(function ($q) use ($values) {
                        foreach ($values as $value) {
                            if (strtolower($value) === 'remote') {
                                $q->orWhere('is_remote', true);
                            } else {
                                $q->orWhereHas('locations', function ($locQuery) use ($value) {
                                    $locQuery->where('city', $value)
                                           ->orWhere('state', $value)
                                           ->orWhere('country', $value);
                                });
                            }
                        }
                    });
                } else {
                    return $query->whereHas($relation, function ($subQuery) use ($values) {
                        $subQuery->whereIn('name', $values);
                    });
                }
            case 'EXISTS':
                return $query->whereHas($relation);
            default:
                throw new \InvalidArgumentException("Unsupported relationship operator: $operator");
        }
    }
    
    /**
     * Apply an attribute-based condition to the query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $condition
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyAttributeCondition(Builder $query, array $condition): Builder
    {
        $name = $condition['name'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        
        // First, get the attribute by name
        $attribute = Attribute::where('name', $name)->first();
        
        if (!$attribute) {
            // If attribute doesn't exist, return the query as is
            return $query;
        }
        
        $attributeId = $attribute->id;
        
        // Special handling for the IN operator
        if ($operator === 'IN') {
            return $query->whereHas('attributeValues', function ($subQuery) use ($attributeId, $value) {
                $subQuery->where('attribute_id', $attributeId)
                       ->whereIn('value', $value);
            });
        }
        
        // Handle the LIKE operator
        if ($operator === 'LIKE' && !Str::startsWith($value, '%') && !Str::endsWith($value, '%')) {
            $value = "%$value%";
        }
        
        // Handle other operators
        return $query->whereHas('attributeValues', function ($subQuery) use ($attributeId, $operator, $value) {
            $subQuery->where('attribute_id', $attributeId)
                   ->where('value', $operator, $value);
        });
    }
} 