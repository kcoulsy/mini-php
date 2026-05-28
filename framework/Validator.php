<?php

declare(strict_types=1);

namespace Framework;

final class Validator
{
  /** @var array<string, mixed> */
  private array $data;

  /** @var array<string, list<string>> */
  private array $errors = [];

  /** @var array<string, list<string|callable>> */
  private array $rules;

  /** @var array<string, string> */
  private array $messages;

  /**
   * @param array<string, mixed> $data
   * @param array<string, string|list<string|callable>> $rules
   * @param array<string, string> $messages
   */
  private function __construct(array $data, array $rules, array $messages)
  {
    $this->data = $data;
    $this->rules = $rules;
    $this->messages = $messages;
    $this->validate();
  }

  /**
   * @param array<string, mixed> $data
   * @param array<string, string|list<string|callable>> $rules
   * @param array<string, string> $messages
   */
  public static function make(array $data, array $rules, array $messages = []): self
  {
    return new self($data, $rules, $messages);
  }

  public function fails(): bool
  {
    return $this->errors !== [];
  }

  public function passes(): bool
  {
    return !$this->fails();
  }

  /** @return array<string, list<string>> */
  public function errors(): array
  {
    return $this->errors;
  }

  public function get(string $field, mixed $default = ''): mixed
  {
    return $this->data[$field] ?? $default;
  }

  /** @return array<string, mixed> */
  public function validated(): array
  {
    $validated = [];

    foreach (array_keys($this->rules) as $field) {
      if (array_key_exists($field, $this->data)) {
        $validated[$field] = $this->data[$field];
      }
    }

    return $validated;
  }

  private function validate(): void
  {
    foreach ($this->rules as $field => $ruleSet) {
      $rules = $this->parseRules($ruleSet);

      foreach ($rules as $rule) {
        if (is_string($rule)) {
          $message = $this->applyRule($field, $rule);
        } else {
          $message = $rule($this->data[$field] ?? null, $field, $this->data);
        }

        if ($message !== null) {
          $this->addError($field, $message);

          break;
        }
      }
    }
  }

  /**
   * @param string|list<string|callable> $ruleSet
   * @return list<string|callable>
   */
  private function parseRules(string|array $ruleSet): array
  {
    if (is_string($ruleSet)) {
      return array_values(array_filter(
        array_map('trim', explode('|', $ruleSet)),
        static fn (string $rule): bool => $rule !== '',
      ));
    }

    return array_values($ruleSet);
  }

  private function applyRule(string $field, string $rule): ?string
  {
    $value = $this->data[$field] ?? null;

    if ($rule === 'trim') {
      if (is_string($value)) {
        $this->data[$field] = trim($value);
      }

      return null;
    }

    if ($rule === 'required') {
      $current = $this->data[$field] ?? null;

      if ($current === null || $current === '') {
        return $this->message($field, 'required', 'This field is required.');
      }

      return null;
    }

    if ($rule === 'email') {
      $current = (string) ($this->data[$field] ?? '');

      if ($current === '' || filter_var($current, FILTER_VALIDATE_EMAIL) === false) {
        return $this->message($field, 'email', 'A valid email is required.');
      }

      return null;
    }

    if (str_starts_with($rule, 'max:')) {
      $max = (int) substr($rule, 4);
      $current = (string) ($this->data[$field] ?? '');

      if (mb_strlen($current) > $max) {
        return $this->message(
          $field,
          'max',
          "This field must be {$max} characters or fewer.",
          [':max' => (string) $max],
        );
      }

      return null;
    }

    if (str_starts_with($rule, 'min:')) {
      $min = (int) substr($rule, 4);
      $current = (string) ($this->data[$field] ?? '');

      if (mb_strlen($current) < $min) {
        return $this->message(
          $field,
          'min',
          "This field must be at least {$min} characters.",
          [':min' => (string) $min],
        );
      }

      return null;
    }

    if ($rule === 'confirmed') {
      if (str_ends_with($field, '_confirmation')) {
        $baseField = substr($field, 0, -13);
        $current = $this->data[$baseField] ?? null;
        $confirmation = $this->data[$field] ?? null;
      } else {
        $current = $this->data[$field] ?? null;
        $confirmation = $this->data[$field . '_confirmation'] ?? null;
      }

      if ($current !== $confirmation) {
        return $this->message($field, 'confirmed', 'Confirmation does not match.');
      }

      return null;
    }

    return null;
  }

  /** @param array<string, string> $replacements */
  private function message(string $field, string $rule, string $default, array $replacements = []): string
  {
    $key = "{$field}.{$rule}";
    $text = $this->messages[$key] ?? $default;

    foreach ($replacements as $placeholder => $value) {
      $text = str_replace($placeholder, $value, $text);
    }

    return $text;
  }

  private function addError(string $field, string $message): void
  {
    $this->errors[$field] ??= [];
    $this->errors[$field][] = $message;
  }
}
