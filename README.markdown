
# PDO2

Lightweight (5.6KB) [PDO](http://php.net/pdo) wrapper to accelerate database development.

## Usage

```php
$db = new PDO2($yourPdoInstance)
```

All PDO methods are still available directly,
but the instance is augmented with six extra methods.

1. **execute**(string $statement, array $input_parameters = []) : [PDOStatement](http://php.net/manual/en/class.pdostatement.php)

```php
$stm = $db->execute("SELECT * FROM wat WHERE id = ?", [12]);
// shortcut of
$stm = $this->prepare("SELECT * FROM wat WHERE id = ?");
$stm->execute([12]);
```
2. **select**($table, $params = [], $extra = '', $values = []) : [PDOStatement](http://php.net/manual/en/class.pdostatement.php)

```php
$db->select('actors');
$db->select('actors', ['first_name' => 'Al']);
$db->select('actors', ['age > ?' => 52]);
$db->select('id,age,name from actors', ['age > ?' => 52]);
// Associative array creates AND, list array creates OR.
$db->select('actors', ['a' => 1, [['b' => 2], ['c' => 3]]])
// SELECT * FROM actors WHERE a = ? AND ((b = ?) OR (c = ?))
```

3. **insert**($table, array $params) : [PDOStatement](http://php.net/manual/en/class.pdostatement.php)

```php
$db->insert('actors', ['first_name' => 'Humphrey', 'last_name' => 'Bogart', 'age' => 57]);
```

4. **update**($table, array $params, array $where) : [PDOStatement](http://php.net/manual/en/class.pdostatement.php)

```php
$db->update('actors', ['first_name' => 'Tommy'], ['id' => 3])
```

5. **delete**($table, array $params) : [PDOStatement](http://php.net/manual/en/class.pdostatement.php)

```php
$db->delete('actors', ['id' => 3]);
$db->delete('actors', []); // empty where part cannot be omitted.
```

6. **count**($table, array $params = null) : int

```php
$db->count('actors', ['first_name' => 'Tom'])
```
