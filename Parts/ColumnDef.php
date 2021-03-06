<?php

namespace Silver\Database\Parts;

class ColumnDef extends Part
{

	protected $name;
	protected $type;
	protected $args;

	protected $unsigned = false;
	protected $nullable = null;
	protected $default = null;
	protected $primary = false;
	protected $autoincrement = false;
	protected $unique = false;

	protected $references = null;
	protected $onDelete = null;
	protected $onUpdate = null;

	public function __construct(string $name, string $type, ...$args)
	{
		$this->name = $name;
		$this->type = $type;
		$this->args = $args;
	}

	public function getName(): string
	{
		return $this->name;
	}

	protected static function compile(object $q): array
	{
		$name = Name::ensure($q->name);

		$sql = $name
			 . static::compileType($q)
			 . static::compileUnsigned($q)
			 . static::compileNullable($q)
			 . static::compilePrimary($q)
			 . static::compileAutoInc($q)
			 . static::compileUnique($q)
			 . static::compileDefault($q);

		if ($index = static::compileReference($q, $name)) {
			$sql .= ', ' . $index;
		}

		return [ $sql ];
	}

	protected static function mapType(string $type, array $args): array
	{
		return [$type, $args];
	}

	protected static function compileType(object $q): string
	{
		$type = $q->type;
		$args = $q->args;
		list ($type, $args) = static::mapType($type, $args);
		$r = ' ' . $type;
		if (count($args) > 0) {
			$args = array_map(
				function ($arg) {
					return Literal::ensure($arg);
				}, $args
			);
			$r .= '(' . implode(', ', $args) . ')';
		}
		return $r;
	}

	protected static function compileUnsigned(object $q): string
	{
		return $q->unsigned ? ' UNSIGNED' : '';
	}

	protected static function compileNullable(object $q): string
	{
		return $q->nullable == false ? ' NOT NULL' : '';
	}

	protected static function compilePrimary(object $q): string
	{
		return $q->primary ? ' PRIMARY KEY' : '';
	}

	protected static function compileAutoInc(object $q): string
	{
		return $q->autoincrement ? ' AUTO_INCREMENT' : '';
	}

	protected static function compileUnique(object $q): string
	{
		return $q->unique ? ' UNIQUE' : '';
	}

	protected static function compileDefault(object $q): string
	{
		if ($q->default !== null) {
			return ' DEFAULT ' . Literal::ensure($q->default);
		}
		return '';
	}

	protected static function compileReference(object $q, string $name): string
	{
		if ($q->references !== null) {
			$references = Column::ensure($q->references, true);
			if (!($references instanceof Column)) {
				throw new \Exception("Refrences must be Column!");
			}

			$sql = " FOREIGN KEY ({$name}) REFERENCES " . $references->getTable() . '(' . $references->getColumn() . ')';

			if ($q->onUpdate !== null) {
				$sql .= ' ON UPDATE';
				switch($q->onUpdate) {
				case 'cascade': $sql .= 'CASCADE';
					break;
				case 'null': $sql .= 'SET NULL';
					break;
				default:
					throw new \Exception("On update mode '{$q->onUpdate}' not in (cascade, null).");
				}
			}

			if ($q->onDelete !== null) {
				$sql .= ' ON DELETE ';
				switch($q->onDelete) {
				case 'cascade': $sql .= 'CASCADE';
					break;
				case 'null': $sql .= 'SET NULL';
					break;
				default:
					throw new \Exception("On delete mode '{$q->onDelete}' not in (cascade, null).");
				}
			}

			return $sql;
		}
		return '';
	}
}
