<?php
require 'PDO2.php';

class PDO2Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->db = new PDO2('sqlite::memory:');
        $this->db->exec("
            CREATE TABLE actors (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                first_name VARCHAR(45) NOT NULL,
                last_name VARCHAR(45) NOT NULL
            )
        ");
        $this->db->exec("
            CREATE TABLE films (
                id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                code CHAR(40) NOT NULL,
                description TEXT NOT NULL,
                is_rotten BOOLEAN NOT NULL,
                grossing DOUBLE NOT NULL DEFAULT 0,
                rating FLOAT NOT NULL DEFAULT 0,
                picture BLOB,
                dvd_release_at DATETIME,
                theatre_release_on DATE,
                release_year YEAR NOT NULL DEFAULT 1982
            )
        ");
        $this->db->exec("
            CREATE TABLE films_actors (
                film_id INTEGER NOT NULL REFERENCES films (id),
                actor_id INTEGER NOT NULL REFERENCES actors (id)
            )
        ");
        $this->db->exec("INSERT INTO actors VALUES (1, 'Al', 'Pacino')");
        $this->db->exec("INSERT INTO actors VALUES (2, 'Wesley', 'Snipes')");
        $this->db->exec("INSERT INTO actors VALUES (3, 'Tom', 'Cruise')");
    }

    /**
     * @expectedException PDOException
     * @expectedExceptionMessage SQLSTATE[HY000]: General error: 1 no such table: not_found
     */
    public function testExceptionsByDefault()
    {
        $this->db->count('not_found');
    }

    public function testSelect()
    {
        $this->assertEquals(3, count($this->db->select('actors')->fetchAll()));
        $expected = array( (object) array('id' => '1', 'first_name' => 'Al', 'last_name' => 'Pacino') );
        $this->assertEquals($expected, $this->db->select('actors', array('first_name' => 'Al'))->fetchAll());
        $this->assertEquals($expected, $this->db->select('actors', array('first_name = ?' => 'Al'))->fetchAll());
        $this->assertEquals($expected, $this->db->select('actors', array('first_name' => 'Al'), 'LIMIT 1')->fetchAll());
        $this->assertEquals($expected, $this->db->select('actors', array('first_name' => 'Al'), 'LIMIT ?', array(1))->fetchAll());
        $this->assertEquals(2, count($this->db->select('actors', 'LIMIT 2')->fetchAll()));
        $this->assertEquals(2, count($this->db->select('actors', 'LIMIT ?', array(2))->fetchAll()));
        $this->assertEquals(2, count($this->db->select('actors', array(array('first_name' => 'Al'), array('last_name' => 'Cruise')))->fetchAll()));
        $expected = array( (object) array('id' => '1') );
        $this->assertEquals($expected, $this->db->select('id from actors', array('id' => '1'))->fetchAll());
    }

    public function testInsert()
    {
        $this->db->insert('actors', array('first_name' => 'Humphrey', 'last_name' => 'Bogart'));
        $this->assertEquals(4, $this->db->lastInsertId());
    }

    public function testUpdate()
    {
        $this->assertEquals(1, $this->db->update('actors', array('first_name' => 'Tommy'), array('id' => 3))->rowCount());
    }

    public function testDelete()
    {
        $this->assertEquals(1, $this->db->delete('actors', array('id' => 3))->rowCount());
        $this->assertEquals(2, $this->db->delete('actors', array())->rowCount());
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->db->count('actors'));
        $this->assertEquals(1, $this->db->count('actors', array('first_name' => 'Tom')));
    }

    public function testWhere()
    {
        $values = array();
        $this->assertEquals('', $this->db->where(null, $values));
        $this->assertEquals('', $this->db->where(array(), $values));
        $this->assertEquals('', $this->db->where(array(array()), $values));
        $this->assertEquals('WHERE a = ? AND b = ?', $this->db->where(array('a' => 1, 'b' => 2), $values));
        $this->assertEquals(array(1, 2), $values);
        $values = array();
        $this->assertEquals('WHERE (a = ?) OR (b = ?)', $this->db->where(array(array('a' => 1), array('b' => 2)), $values));
        $values = array();
        $this->assertEquals('WHERE a = ? AND ((b = ?) OR (c = ?))', $this->db->where(array('a' => 1, array(array('b' => 2), array('c' => 3))), $values));
        $values = array();
        $this->assertEquals('WHERE a = ?', $this->db->where(array('a' => 1, array()), $values));
        $this->assertEquals(array(1), $values);
    }

}
